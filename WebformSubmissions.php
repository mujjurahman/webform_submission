<?php

namespace Drupal\pfe_webform_submission\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * {@inheritdoc}
 */
class WebformSubmissions extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'pfe_monthly_report_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $database = \Drupal::database();
    $query = $database->select('taxonomy_term__field_workflow_id', 'e');
    $query->leftJoin('taxonomy_term__field_color', 'fc', "fc.entity_id = e.entity_id");
    $query->leftJoin('taxonomy_term_field_data', 'fd', "fd.tid = e.entity_id");
    $query->fields('e', ["field_workflow_id_value"]);
    $query->fields('fc', ["field_color_value"]);
    $query->fields('fd', ["tid", "name"]);
    $color_results = $query->execute()->fetchAll(\PDO::FETCH_ASSOC);
    foreach ($color_results as $color_result) {
      $color_data[$color_result['tid']] = trim($color_result['field_color_value']);
      $states[$color_result['tid']] = $color_result['name'];
    }
    $request = \Drupal::request();
    $state = $request->get('state');
    $id = $request->get('id');
    $expediteur = $request->get('expediteur');
    $form['filters']['#prefix'] = "<div class='webform-submission-filters'>";
    $form['filters']['new_state'] = [
      '#type' => 'select',
      '#title' => t('Etat'),
      '#empty_option' => t('- Any -'),
      '#options' => $states,
      '#default_value' => $state ?: '',
    ];
    $form['filters']['id'] = [
      '#type' => 'textfield',
      '#title' => t('Id'),
      '#default_value' => $id ?: '',
    ];
    $form['filters']['expediteur'] = [
      '#type' => 'textfield',
      '#title' => t('Expéditeur'),
      '#default_value' => $expediteur ?: '',
    ];
    $form['filters']['#suffix'] = "</div>";
    $form['filters']['actions'] = ['#type' => 'actions'];
    $form['filters']['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => t('Filter'),
    ];
    $form['filters']['actions']['reset'] = [
      '#type' => 'submit',
      '#value' => t('Reset'),
    ];
    $form['#attached']['library'][] = 'pfe_webform_log/workflow_color';
    $form['#attached']['drupalSettings']['color_codes'] = $color_data;
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $triggered_element = (string) $form_state->getTriggeringElement()['#value'];
    if ($triggered_element == $this->t("Filter")) {
      $new_state = $values['new_state'];
      $expediteur = $values['expediteur'];
      $id = $values['id'];
      $form_state->setRedirect('pfe_webform_submission.webform_submission', [
        'webform' => "contact",
        'id' => $id,
        'state' => $new_state,
        'expediteur' => $expediteur,
      ]);
    }
    else {
      $form_state->setRedirect('pfe_webform_submission.webform_submission', [
        'webform' => "contact",
      ]);
    }
  }

}
