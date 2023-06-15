<?php

namespace Drupal\pfe_webform_submission\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\taxonomy\Entity\Term;

/**
 * {@inheritdoc}
 */
class WebformSubmissions extends ControllerBase {

  /**
   * {@inheritdoc}
   */
  public function content() {
    $header = [
      'id' => [
        'data' => $this->t('ID'),
        'specifier' => 'sid',
      ],
      'de' => $this->t('De'),
      'now' => $this->t('Nom'),
      'objet' => $this->t('Objet'),
      'recu_le' => [
        'data' => $this->t('Reçu le'),
        'specifier' => 'created',
      ],
      'traite_le' => $this->t('Traité le'),
      'referer' => $this->t('Referer'),
      'argus' => $this->t('Nº Argus'),
      'statut' => $this->t('Statut'),
      'operations' => $this->t('Opérations'),
    ];
    $request = \Drupal::request();
    $state = $request->get('state');
    $id = $request->get('id');
    $expediteur = $request->get('expediteur');
    $storage = \Drupal::entityTypeManager()->getStorage('webform_submission');
    $query = $storage->getQuery();
    $query->tableSort($header);
    if ($id) {
      $query->condition("serial", $id);
    }
    if (!$state) {
      $query->pager(50);
    }
    $wids = $query->accessCheck(TRUE)->execute();
    $query1 = $storage->getQuery();
    $wids_count = $query1->accessCheck(TRUE)->execute();
    $rows = [];
    $date_formatter = \Drupal::service('date.formatter');
    $i = 0;
    foreach ($storage->loadMultiple($wids) as $webformSubmission) {
      $submitted_data = $webformSubmission->getData();
      $id = $webformSubmission->id();
      $serial = $webformSubmission->serial();
      $workflow_id = $submitted_data['workflow_id'];
      if ($state && $state != $workflow_id) {
        continue;
      }
      $i++;
      if ($workflow_id) {
        $workflow_load = Term::load($workflow_id);
        $workflow_label = $workflow_load->label();
        $workflow_color = $workflow_load->get("field_color")->value;
      }
      $row = [];
      $row[] = $this->t("<a href='/admin/structure/webform/manage/contact/submission/$id'>$serial</a>");
      $row[] = $submitted_data['email'];
      $row[] = $submitted_data['name'] . ", " . $submitted_data['first_name'];
      $row[] = $submitted_data['subject'];
      $created = $webformSubmission->get('created')->value;
      $row[] = [
        'data' => [
          '#theme' => 'time',
          '#text' => $date_formatter->format($created),
          '#attributes' => [
            'datetime' => $date_formatter->format($created, 'custom', 'Y-m-d H:i'),
          ],
        ],
      ];
      $changed = $webformSubmission->get('changed')->value;
      $row[] = [
        'data' => [
          '#theme' => 'time',
          '#text' => $date_formatter->format($changed),
          '#attributes' => [
            'datetime' => $date_formatter->format($changed, 'custom', 'Y-m-d H:i'),
          ],
        ],
      ];
      $row[] = $submitted_data['referer'];
      $row[] = $submitted_data['argus_number'];
      $class = " webform-workflow-state-color-white";
      if ($workflow_color == "#FFFFFF" || $workflow_color == "#FFF" || $workflow_color == "#FFFF00") {
        $class = " webform-workflow-state-color-none";
      }
      $row[] = $this->t("<div class='webform-workflow-state webform-workflow-state-label $class' style='background-color: $workflow_color'>$workflow_label</div>");
      $operations = $this->t("<a href='/admin/structure/webform/manage/contact/submission/$id'>Afficher</a> | <a href='/admin/structure/webform/manage/contact/submission/$id/edit'>Edit</a> | <a href='/admin/structure/webform/manage/contact/submission/$id/delete'>Delete</a>");
      $row[] = $operations;
      $rows[] = $row;
    }
    if ($rows) {
      $build['count_data'] = [
        '#markup' => $this->t("Displaying @present - @present_count submissions of @total", [
          "@present" => ($_GET['page'] * 50) + 1,
          "@present_count" => $i,
          "@total" => count($wids_count),
        ]),
      ];
    }
    $build['filter_form'] = $this->buildFilterForm();
    $build['table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No submissions has been found.'),
    ];
    $build['pager'] = [
      '#type' => 'pager',
    ];
    return $build;
  }

  /**
   * Build the filter form.
   *
   * @return array
   *   A render array representing the filter form.
   */
  protected function buildFilterForm() {
    return \Drupal::formBuilder()
      ->getForm('\Drupal\pfe_webform_submission\Form\WebformSubmissions');
  }

}
