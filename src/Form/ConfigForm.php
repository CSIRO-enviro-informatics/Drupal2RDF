<?php

namespace Drupal\semantic_map\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\Yaml\Yaml;

class ConfigForm extends FormBase{

  public function nextSubmit(array &$form, FormStateInterface &$form_state) {
    $pageNum = $form_state->get('page_num');
    $prevPage = $pageNum-1;
    $nextPage = $pageNum+1;

    $form_state->set(['page_values', $pageNum], $form_state->getValues());

    if ($form_state->has(['page_values', $nextPage])) {
      $form_state->setValues($form_state->get(['page_values', $nextPage]));
    }

    // When form rebuilds, build method would be chosen based on to page_num.
    $form_state->set('page_num', $nextPage);
    $form_state->setRebuild();
  }

  public function getFormId(){
    return 'config_form';
  }

  public function ontology_get_labels(){
    $data = __DIR__ . '/../../resources/yaml/ontologies.yml';
    $yaml = new Yaml();
    $ont_parent_list = $yaml->parse(file_get_contents($data));
    $ont_label_list = array_column($ont_parent_list, 'label');

    return $ont_label_list;
  }

  public function ontology_get_ontology(string $ontologyName){
    $data = __DIR__ . '/../../resources/yaml/ontologies.yml';
    $yaml = new Yaml();
    $ont_parent_list = $yaml->parse(file_get_contents($data));
    $ontology = $ont_parent_list[$ontologyName];

    return $ontology;
  }

  

  public function buildForm(array $form, FormStateInterface $form_state){
    // Display page 2 if $form_state->get('page_num') == 2.
    if ($form_state->has('page_num') && $form_state->get('page_num') == 2){
      return $this->buildFormPageTwo($form, $form_state);
    }
    elseif ($form_state->has('page_num') && $form_state->get('page_num') == 3){
      return $this->buildFormPageThree($form, $form_state);
    }

    // set initial page_num
    if (!$form_state->has('page_num')){
      $form_state->set('page_num', 1);
    }

    $form['content-type'] = [
      '#title' => $this->t('Content Type'),
      '#description' => $this->t('Select the Content Type you want to begin mapping to'),
      '#type' => 'select',
      '#options' => node_type_get_names(),
        //'#default_value' => $form_state->getValue('rdf-type', ''),
    ];

    $form['ontology-type'] = [
      '#title' => $this->t('Ontology Type'),
      '#description' => $this->t('Select the Ontology you want to use'),
      '#type' => 'select',
      '#options' => $this->ontology_get_labels(),
        //'#default_value' => $form_state->getValue('rdf-type', ''),
    ];

    // next button
    $form['actions'] = array('#type' => 'actions');
    $form['actions']['next'] = [
      '#type' => 'submit',
      '#value' => $this->t('Next >>'),
      '#button_type' => 'primary',
      '#submit' => array(array($this, 'nextSubmit')),
      '#validate' => array(array($this, 'nextValidate')),
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Calculate'),
    ];

    return $form;
  }

  protected function buildFormPageTwo(array $form, FormStateInterface $form_state){
    $form['content-type'] = [
      '#title' => $this->t('Content Type'),
      '#description' => $this->t('PAGE 2'),
      '#type' => 'select',
      '#options' => node_type_get_names(),
        //'#default_value' => $form_state->getValue('rdf-type', ''),
    ];


    // next button
    $form['actions'] = array('#type' => 'actions');
    $form['actions']['next'] = [
      '#type' => 'submit',
      '#value' => $this->t('Next >>'),
      '#button_type' => 'primary',
      '#submit' => array(array($this, 'nextSubmit')),
      '#validate' => array(array($this, 'nextValidate')),
    ];
    return $form;
  }

  protected function buildFormPageThree(array $form, FormStateInterface $form_state){
    $form['content-type'] = [
      '#title' => $this->t('Content Type'),
      '#description' => $this->t('PAGE 3'),
      '#type' => 'select',
      '#options' => node_type_get_names(),
      //'#default_value' => $form_state->getValue('rdf-type', ''),
    ];
    return $form;
  }

  public function nextValidate(array $form, FormStateInterface $form_state) {
  // @TODO validate if required.
  }

  public function pageTwoBackValidate(array $form, FormStateInterface $form_state) {
  // @TODO validate if required.
  }

  public function submitForm(array &$form, FormStateInterface $form_state){
    $value = $form_state->getValue('ontology-type' , '#options');
    $arr = $this->ontology_get_labels();
    $val = $arr[$value];

    drupal_set_message($val);
  }
}
