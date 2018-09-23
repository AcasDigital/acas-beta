<?php
/**
 * @file
 * Contains \Drupal\production\Controller\ProductionController.
 * All code for syncing/deploying content to the Production site
 */

namespace Drupal\production\Controller;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use ZipArchive;
use Drupal\Component\Utility\Html;

class ProductionController extends ControllerBase {

  public function sync_prod() {
    $sync_type = 1;
    if (isset($_POST['sync_type'])) {
      $sync_type = (int)$_POST['sync_type'];
    }
    $cloudfront = FALSE;
    if (isset($_POST['cloudfront'])) {
      $cloudfront = (int)$_POST['cloudfront'];
    }
    $this->production_sync_prod($sync_type, $cloudfront);
    return array(
      '#markup' => '<h3>Finished.</h3><h3>Now testing Production site</h3><div id="test-target"><div class="target">Starting processes. Please wait... </div></div>',
      '#attached' =>
        array(
          'library' => array('production/test_prod')
        )
    );
  }
  
  public function test_prod() {
    if (!_is_site('uat')) {
      drupal_set_message("Test Production can only be run from the UAT site!", 'error');
      return array('#markup' => '<h3>Not allowed</h3>');
    }
    return array(
      '#markup' => '<h3>Testing content on Production site is the same as UAT</h3><div id="test-target"><div class="target">Starting processes. Please wait... </div></div>',
      '#attached' =>
        array(
          'library' => array('production/test_prod')
        )
    );
  }
  
  /**
  * sync_update().
  * PROD
  * The base64 encoded zip file from UAT
  */
  public function sync_update($sync_type = 1, $cloudfront = FALSE) {
    \Drupal::logger('acas_sync')->notice('Sync update. sync_type = ' . $sync_type . ', cloudfront = ' . $cloudfront);
    $uuid = \Drupal::config('system.site')->get('uuid');
    if ($uuid == $_POST['UUID']) {
      $config_factory = \Drupal::configFactory();
      $config = \Drupal::config('acas.settings');
      if ($sync_type < 3) {
        $configs = [];
        $exclude = preg_split('/\r\n|\r|\n/', $config->get('config'));
        foreach($exclude as $e) {
          $configs[] = $config_factory->getEditable($e);
        }
        file_put_contents('/tmp/sync.zip', base64_decode($_POST['data']));
        $zip = new ZipArchive();
        $zip->open('/tmp/sync.zip');
        $zip->extractTo('/tmp/');
        $zip->close();
        $connection = \Drupal\Core\Database\Database::getConnection()->getConnectionOptions();
        $cmd = 'mysql -u ' . $connection['username'] . ' -p' . $connection['password'] . ' -h ' . $connection['host'] . ' ' . $connection['database'] . ' < /tmp/' . $_POST['file'];
        exec($cmd);
        unlink('/tmp/sync.zip');
        unlink('/tmp/' . $_POST['file']);
        foreach($configs as $c) {
          $c->save(TRUE);
        }
        // Clear search index and re-index
        $old_path = getcwd();
        chdir('/var/www/html/');
        shell_exec('drush search-api-reindex');
        shell_exec('drush cron');
        chdir($old_path);
        // Clear the cloudfront cache
        if ($sync_type == 2) {
          $this->sync_cleanup($cloudfront);
        }else if ($cloudfront){
          // Clear the entire cache
          $this->production_cloudfront_invalidate(TRUE);
        }
      }else{
        // Code only
        $old_path = getcwd();
        chdir('/var/www/html/');
        $invalidate_all = (bool)trim(shell_exec('./git_pull.sh'));
        chdir($old_path);
        if ($invalidate_all && $cloudfront) {
          $this->production_cloudfront_invalidate(TRUE);
        }
        drupal_flush_all_caches();
      }
      return new JsonResponse('ok');
    }
    \Drupal::logger('acas_sync')->error('UUID does not match!');
    return new JsonResponse('error');
  }
  
  /**
  * sync_cleanup().
  * PROD
  * Called after the DB update from UAT
  * Runs git_pull.sh that performs a "git pull origin master" that returns 0 if nothing
  * to pull or 1 if any changes. If 1 then invalidate all content on CloudFront
  * in case of any CSS changes else invalidate only new/changed content.
  */
  public function sync_cleanup($cloudfront) {
    \Drupal::logger('acas_sync')->notice('Sync cleanup 1. cloudfront = ' . $cloudfront);
    $old_path = getcwd();
    chdir('/var/www/html/');
    $invalidate_all = (bool)trim(shell_exec('./git_pull.sh'));
    if (!$invalidate_all) {
      $invalidate_all = FALSE;
    }
    chdir($old_path);
    drupal_flush_all_caches();
    \Drupal::service('simple_sitemap.generator')->generateSitemap();
    if ($cloudfront) {
      $this->production_cloudfront_invalidate($invalidate_all);
    }
    \Drupal::logger('acas_sync')->notice('Sync cleanup 2. invalidate_all = ' . $invalidate_all . ', cloudfront = ' . $cloudfront);
    return new JsonResponse('ok');
  }
  
  /**
  * sync_prod_data().
  * UAT
  * Builds the Json data for testing that all content has been synced
  */
  public function sync_prod_data() {
    $config_factory = \Drupal::configFactory();
    $config = $config_factory->getEditable('acas.settings');
    $nodes = \Drupal\node\Entity\Node::loadMultiple();
    $return = ['prod' => $config->get('prod')];
    foreach($nodes as $node) {
      if ($node->isPublished()) {
        $return['nodes'][] = [
          'title' => $node->getTitle(),
          'url' => $node->toUrl()->toString(),
          'changed' => $node->getChangedTime(),
        ];
      }
    }
    return new JsonResponse($return);
  }
  
  /**
  * cloudfront_invalidate().
  * PROD
  * Invalidate all content in CloudFront
  */
  public function cloudfront_invalidate() {
    $output = '<h1>Invalidate all CloudFront content</h1>';
    $result = $this->production_cloudfront_invalidate(TRUE);
    if (strpos($result, '<?xml version="1.0"?>') !== FALSE) {
      $a = explode('<?xml version="1.0"?>', $result);
      $b = explode('<InvalidationBatch>', $a[1]);
      if (count($b) > 1) {
        $c = explode('<CallerReference>', $b[1]);
        $data = str_replace('Path', 'div', $c[0]);
        return array('#markup' => $output . '<h2>Invalidated paths</h2><div class="code">' . $data . '</div><br />');
      }else{
        return array('#markup' => $output . $result);
      }
    }else{
      return array('#markup' => $output . $result);
    }
  }
  
  /**
  * deploy_update().
  * PROD
  */
  public function deploy_update() {
    $uuid = \Drupal::config('system.site')->get('uuid');
    if ($uuid == $_POST['UUID']) {
      if ($data = json_decode(@$_POST['data'])) {
        $nodeIds = [];
        foreach($data as $d) {
          if ($node = \Drupal\node\Entity\Node::load($d->nid)) {
            if ($node->getType() == 'page') {
              $node->setTitle($d->title);
              $node->body->value = $d->content;
              $node->body->summary = $d->summary;
            }else{
              $node->setTitle($d->title);
              $node->field_summary->value = $d->content;
              $node->field_summary->summary = $d->summary;
            }
            $node->save();
            $nodeIds[] = $node->id();
          }
        }
        $this->production_cloudfront_invalidate(FALSE, $nodeIds);
      }
      return new JsonResponse('ok');
    }
    return new JsonResponse('error');
  }
  
  // *** Private functions ***
  
  /**
  * production_sync_prod().
  * UAT
  * Zips a subset of the DB and posts the base64 encoded zip file to prod
  */
  private function production_sync_prod($sync_type = 1, $cloudfront = FALSE) {
    \Drupal::logger('acas_sync')->notice('Sync prod. sync_type = ' . $sync_type . ', cloudfront = ' . $cloudfront);
    $config = \Drupal::config('acas.settings');
    if ($sync_type < 3) {
      $connection = \Drupal\Core\Database\Database::getConnection()->getConnectionOptions();
      $output = '';
      $file = 'DB_' . time() . '.sql';
      $path = '/tmp/' . $file;
      $exclude = preg_split('/\r\n|\r|\n/', $config->get('tables'));
      $tables = '';
      $database = $connection['database'];
      foreach($exclude as $t) {
        $tables .= " --ignore-table=$database.$t";
      }
      $cmd = 'mysqldump -u ' . $connection['username'] . ' -p' . $connection['password'] . ' -h ' . $connection['host'] . ' ' . $connection['database'] . $tables . ' > ' . $path;
      exec($cmd);
      // Zip, Base64, curl to prod
      $zip_file = str_replace('.sql', '.zip', $path);
      $zip = new ZipArchive();
      $zip->open($zip_file, constant("ZipArchive::CREATE"));
      $zip->addFile($path, $file);
      $zip->close();
      $encoded = base64_encode(file_get_contents($zip_file));
      unlink($path);
      unlink($zip_file);
    }else{
      // Code only
      $encoded = '';
      $file = '';
    }
    $POST_DATA = array(
      'data' => $encoded,
      'UUID' => \Drupal::config('system.site')->get('uuid'),
      'file' => $file,
    );
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $config->get('prod') . '/sync-update/' . $sync_type . '/' . $cloudfront);
    curl_setopt($curl, CURLOPT_TIMEOUT, 30);
    curl_setopt($curl, CURLOPT_POST, 1);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $POST_DATA);
    $response = curl_exec($curl);
    curl_close ($curl);
    if ($sync_type == 1) {
      drupal_set_message('Finished syncing content (no code) to Production');
    }else if ($sync_type == 2){
      drupal_set_message('Finished syncing content and code to Production');
    }else {
      drupal_set_message('Finished syncing code (no content) to Production');
    }
  }
  
  /**
  * production_cloudfront_invalidate().
  * Invalidate the CloudFront cache for new content
  * or if $all, invalidate all content
  */
  private function production_cloudfront_invalidate($all = FALSE, $nodeIds = FALSE) {
    \Drupal::logger('acas_sync')->notice('Cloudfront invalidate. all = ' . $all);
    $config_factory = \Drupal::configFactory();
    $config = $config_factory->getEditable('cloudfront.settings');
    $paths = '';
    if ((!$last = $config->get('last_sync')) || $all) {
      $last = 0;
    }
    if ($nodeIds) {
      foreach($nodeIds as $n) {
        $path = \Drupal::service('path.alias_manager')->getAliasByPath('/node/' . $n);
        $paths .= '<Path>' . $path . '</Path>';
        if ($path == '/home') {
          $paths .= '<Path>/</Path>';
        }
      }
    }else if (!$all) {
      $query = \Drupal::database()->select('node_field_data', 'nfd');
      $query->fields('nfd', array('nid'));
      $query->condition('nfd.changed', $last, '>');
      $result = $query->execute();
      if ($nodeIds = $result->fetchCol()) {
        foreach($nodeIds as $n) {
          $path = \Drupal::service('path.alias_manager')->getAliasByPath('/node/' . $n);
          $paths .= '<Path>' . $path . '</Path>';
          if ($path == '/home') {
            $paths .= '<Path>/</Path>';
          }
        }
      }else{
        return 'Nothing to invalidate';
      }
    }else{
      $paths = '<Path>/*</Path>';
    }
    $distribution = $config->get('id');
    $access_key = $config->get('key');
    $epoch = date('U');
    $xml = "<InvalidationBatch>$paths<CallerReference>{$distribution}{$epoch}</CallerReference></InvalidationBatch>";
    $len = strlen($xml);
    $date = gmdate('D, d M Y G:i:s T');
    $sig = base64_encode(
      hash_hmac('sha1', $date, $config->get('secret'), true)
    );
    $msg = "POST /2010-11-01/distribution/{$distribution}/invalidation HTTP/1.0\r\n";
    $msg .= "Host: cloudfront.amazonaws.com\r\n";
    $msg .= "Date: {$date}\r\n";
    $msg .= "Content-Type: text/xml; charset=UTF-8\r\n";
    $msg .= "Authorization: AWS {$access_key}:{$sig}\r\n";
    $msg .= "Content-Length: {$len}\r\n\r\n";
    $msg .= $xml;
    $fp = fsockopen('ssl://cloudfront.amazonaws.com', 443,
      $errno, $errstr, 30
    );
    if (!$fp) {
      return "Connection failed: {$errno} {$errstr}\n";
    }
    fwrite($fp, $msg);
    $resp = '';
    while(! feof($fp)) {
      $resp .= fgets($fp, 1024);
    }
    fclose($fp);
    if (!$nodeIds) {
      $config->set('last_sync', time());
      $config->save();
    }
    return $resp;
  }
}