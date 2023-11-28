<?php

namespace Drupal\dataset_upload\Commands;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Queue\DelayedRequeueException;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Queue\QueueWorkerManagerInterface;
use Drupal\Core\Queue\SuspendQueueException;
use Drupal\dataset_upload\Service\NirdApiClient;
use Drush\Commands\DrushCommands;

/**
 * Drush command file.
 */
class DatasetUploadCommands extends DrushCommands {
  /**
   * Drupal\Core\Logger\LoggerChannelFactoryInterface definition.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * Drupal\Core\Config\ConfigFactoryInterface definition.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;


  /**
   * The queue factory.
   *
   * @var \Drupal\Core\Queue\QueueFactory
   */
  protected $queueFactory;
  /**
   * The queue manager.
   *
   * @var \Drupal\Core\Queue\QueueWorkerManagerInterface
   */
  protected $queueManager;

  /**
   * Drupal\dataset_upload\NirdApiClientInterface definition.
   *
   * @var \Drupal\dataset_upload\Service\NirdApiClient
   */
  protected $nirdApiClient;

  /**
   * Constructor with dependency injection.
   */
  public function __construct(
        LoggerChannelFactoryInterface $loggerFactory,
        ConfigFactoryInterface $configFactory,
        NirdApiClient $nirdApiClient,
        QueueFactory $queue,
        QueueWorkerManagerInterface $queueManager
    ) {
    parent::__construct();
    $this->loggerFactory = $loggerFactory;
    $this->configFactory = $configFactory;
    $this->nirdApiClient = $nirdApiClient;
    $this->queueFactory = $queue;
    $this->queueManager = $queueManager;
  }

  /**
   * A custom Drush command to displays the given text.
   *
   * @command nird:process
   * @aliases nird
   */
  public function processDatasets() {
    $queue = $this->queueFactory->get('nird_email_queue');
    $logger = $this->loggerFactory->get('nird');
    $logger->info("executing nird_email_queue with number of items: " . $queue->numberOfItems());
    $queue_worker = $this->queueManager->createInstance('nird_email_queue');

    while ($item = $queue->claimItem()) {
      $logger->info("Processing: " . $item->data->dataset_id);
      try {
        $queue_worker->processItem($item->data);
        $queue->deleteItem($item);
      }
      catch (DelayedRequeueException $e) {
        // If the worker indicates there is a problem with the whole queue,
        // release the item and skip to the next queue.
        $logger->notice("queue item not ready delaying: " . $item->data->dataset_id);
        $queue->releaseItem($item);

        break;
      }
      catch (SuspendQueueException $e) {
        // If the worker indicates there is a problem with the whole queue,
        // release the item and skip to the next queue.
        $queue->releaseItem($item);
        break;
      }
      catch (\Exception $e) {
        // In case of any other kind of exception, log it and leave the item
        // in the queue to be processed again later.
        watchdog_exception('nird', $e);
      }
    }
  }

}
