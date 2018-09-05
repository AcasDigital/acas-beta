<?php
/**
 * @file
 * Contains \Drupal\general\Controller\SolanaceaeController.
 */

namespace Drupal\general\Controller;
use Drupal\Core\Controller\ControllerBase;
use Drupal\votingapi\Entity\Vote;
use Drupal\votingapi\Entity\VoteType;
use Symfony\Component\HttpFoundation\JsonResponse;
use ZipArchive;
use Dompdf\Dompdf;
use Drupal\Component\Utility\Html;

class GeneralController extends ControllerBase {
  public function searchheader() {
    return array('#markup' => '');
  }
  public function health() {
    return array('#markup' => general_health());
  }
  public function feedback($entity_id, $value) {
    if ($value == 'Yes') {
      $vote_value = 1;
    }else{
      $vote_value = -1;
    }
    
    $entity = $this->entityTypeManager()
      ->getStorage('node')
      ->load($entity_id);
      
    $voteType = VoteType::load('vote');
    $this->entityTypeManager()
      ->getViewBuilder('node')
      ->resetCache([$entity]);
      
    $vote = Vote::create(['type' => 'vote']);
    $vote->setVotedEntityId($entity_id);
    $vote->setVotedEntityType('node');
    $vote->setValueType($voteType->getValueType());
    $vote->setValue($vote_value);
    $vote->save();

    $this->entityTypeManager()
      ->getViewBuilder('node')
      ->resetCache([$entity]);
      
    return new JsonResponse([
      'vote' => $vote_value,
      'message_type' => 'status',
      'operation' => 'voted',
      'message' => t('Your vote was added.'),
    ]);
  }
  
  public function feedback_results() {
    $connection = \Drupal::database();
    $query = $connection->query("SELECT DISTINCT entity_id FROM {votingapi_result} v WHERE v.entity_type = 'node' AND v.type = 'vote'");
    $result = $query->fetchAll();
    $return = [];
    foreach($result as $v) {
      $node = \Drupal\node\Entity\Node::load($v->entity_id);
      $query2 = $connection->query("SELECT * FROM {votingapi_result} v WHERE v.entity_id = " . $v->entity_id);
      $result2 = $query2->fetchAll();
      $vote = [
        'title' => $node->getTitle(),
        'url' => $node->toUrl()->toString(),
      ];
      foreach($result2 as $v2) {
        $vote['vote'][$v2->function] = $v2->value;
      }
      $return[] = $vote;
    }
    return new JsonResponse($return);
  }
  
  public function anything_wrong_results() {
    $return = [];
    $connection = \Drupal::database();
    $query = $connection->query("SELECT s.sid, s.created FROM {webform_submission} s WHERE s.webform_id = 'anything_wrong'");
    $result = $query->fetchAll();
    foreach($result as $sid) {
      $query2 = $connection->query("SELECT name, value FROM {webform_submission_data} WHERE sid = :sid", array('sid' => $sid->sid));
      $result2 = $query2->fetchAll();
      $data = [];
      $data['sid'] = $sid->sid;
      $data['date'] = date('Y-m-d', $sid->created);
      foreach($result2 as $s) {
        if ($s->name == 'email_optional_') {
          $s->name = 'email';
        }
        if ($s->name == 'how_should_we_improve_this_page_') {
          $s->name = 'message';
        }
        if ($s->name == 'name_optional_') {
          $s->name = 'name';
        }
        $data[$s->name] = $s->value;
      }
      $return[] = $data;
    }
    return new JsonResponse($return);
  }
  
  public function guide_print_download($entity_id) {
    return general_guide_page($entity_id);
  }
  
  public function guide_print($entity_id) {
    return general_guide_page($entity_id);
  }
  
  public function page_print($entity_id) {
    $node = \Drupal\node\Entity\Node::load($entity_id);
    $buid = [];
    $build[] = [
      '#type' => 'markup',
      '#markup' => '<div class="col-xs-8 col-sm-6"><section id="block-sitebranding" class="block block-system block-system-branding-block clearfix"><img src="/themes/custom/acas/toplogo.png" alt="Home"></section></div>
        <header id="block-acas-page-title" class="block block-core block-page-title-block clearfix col-xs-12 col-md-7"><h1 class="page-header"><span>' . $node->getTitle() . '</span></h1></header>'
    ];
    $view_builder = \Drupal::entityTypeManager()->getViewBuilder('node');
    $build[] = $view_builder->view($node, 'print_download');
    $build['#attached']['library'][] = 'general/guide_print_modify';
    return $build;
  }
  
  public function guide_download($entity_id) {
    $node = \Drupal\node\Entity\Node::load($entity_id);
    $build = general_guide_page($entity_id);
    $html = general_download_html_alter(drupal_render($build));
    $html = '<html><head><title>' . $node->getTitle() . '</title><style>' . general_download_css() . '</style></head><body>' . $html . '</body></html>';
    $dompdf = new Dompdf(array('enable_remote' => true));
    $dompdf->loadHtml($html);
    $dompdf->render();
    $dompdf->stream(trim(general_taxonomy_path($node->getTitle())) . '.pdf');
    return array(
      '#markup' => '',
    );
  }
  
  public function page_download($entity_id) {
    $node = \Drupal\node\Entity\Node::load($entity_id);
    $buid = [];
    $build[] = [
      '#type' => 'markup',
      '#markup' => '<div class="col-xs-8 col-sm-6"><section id="block-sitebranding" class="block block-system block-system-branding-block clearfix"><img src="https://' . $_SERVER['HTTP_HOST'] . '/themes/custom/acas/toplogo.png" alt="Home"></section></div>
        <header id="block-acas-page-title" class="block block-core block-page-title-block clearfix col-xs-12 col-md-7"><h1 class="page-header"><span>' . $node->getTitle() . '</span></h1></header>'
    ];
    $view_builder = \Drupal::entityTypeManager()->getViewBuilder('node');
    $build[] = $view_builder->view($node, 'print_download');
    $html = general_download_html_alter(drupal_render($build));
    $html = '<html><head><title>' . $node->getTitle() . '</title><style>' . general_download_css() . '</style></head><body>' . $html . '</body></html>';
    $dompdf = new Dompdf(array('enable_remote' => true));
    $dompdf->loadHtml($html);
    $dompdf->render();
    $dompdf->stream(trim(general_taxonomy_path($node->getTitle())) . '.pdf');
    return array(
      '#markup' => '',
    );
  }
  
  public function sync_prod() {
    general_sync_prod();
    return array(
      '#markup' => '<h3>Finished.</h3><h3>Now testing Production site</h3><div id="test-target"><div class="target">Starting processes. Please wait... </div></div>',
    );
  }
  
  public function test_prod() {
    if (!_is_site('uat')) {
      drupal_set_message("Test Production can only be run from the UAT site!", 'error');
      return array('#markup' => '<h3>Not allowed</h3>');
    }
    return array(
      '#markup' => '<h3>Testing content on Production site is the same as UAT</h3><div id="test-target"><div class="target">Starting processes. Please wait... </div></div>',
    );
  }
  
  /**
  * sync_update().
  * PROD
  * The base64 encoded zip file from UAT
  */
  public function sync_update($content_only = TRUE) {
    $uuid = \Drupal::config('system.site')->get('uuid');
    if ($uuid == $_POST['UUID']) {
      $config_factory = \Drupal::configFactory();
      $config = \Drupal::config('acas.settings');
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
      if (!$content_only) {
        sync_cleanup();
      }else{
        general_cloudfront_invalidate(FALSE);
      }
      return new JsonResponse('ok');
    }
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
  public function sync_cleanup() {
    $old_path = getcwd();
    chdir('/var/www/html/');
    $invalidate_all = (bool)trim(shell_exec('./git_pull.sh'));
    chdir($old_path);
    drupal_flush_all_caches();
    \Drupal::service('simple_sitemap.generator')->generateSitemap();
    general_cloudfront_invalidate($invalidate_all);
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
    $result = general_cloudfront_invalidate(TRUE);
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
        general_cloudfront_invalidate(FALSE, $nodeIds);
      }
      return new JsonResponse('ok');
    }
    return new JsonResponse('error');
  }
  
  public function freeze() {
    return array('#markup' => '<p>Content adding/editing is frozen on this site.</p><p>You can still add/edit content on the UAT site</p>');
  }
  
  public function feedback_page($type, $nid) {
    return general_feedback_page($type, $nid);
  }
  
  public function feedback_title($type, $nid) {
    if ($type == 'no') {
      return 'Please tell us why the information did not help.';
    }else{
      return 'What were you looking for?';
    }
  }
}