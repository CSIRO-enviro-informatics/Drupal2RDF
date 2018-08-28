<?php

namespace Drupal\semantic_map\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\Yaml\Yaml;
use Drupal\semantic_map\OntologyClass;

class ConfigForm extends FormBase{

  public $ontology;
  public $ontology_array;

  public function __construct(){
    $this->ontology = new OntologyClass();
    $this->ontology_array = $this->ontology->getArray();
  }

  // handles the "next" button.
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

    // content type drop down
    $form['content-type'] = [
      '#title' => $this->t('Content Type'),
      '#description' => $this->t('Select the Content Type you want to begin mapping to'),
      '#type' => 'select',
      '#required' => TRUE,
      '#options' => node_type_get_names(),
        //'#default_value' => $form_state->getValue('rdf-type', ''),
    ];

    // ontology type drop down
    $form['ontology-type'] = [
      '#title' => $this->t('Ontology'),
      '#description' => $this->t('Select the Ontology you want to use'),
      '#type' => 'select',
      '#required' => TRUE,
      '#options' => $this->ontology->getLabels(),
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


    // DEBUGGING PURPOSES ONLy
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Calculate'),
    ];

    return $form;
  }

  protected function buildFormPageTwo(array $form, FormStateInterface $form_state){
    // values of first page
    $value = $form_state->get(['page_values', 1]);

    // get CT name
    $ct_names = node_type_get_names();
    $ct = $ct_names[$value['content-type']];
    // get ont name
    $ont_names = $this->ontology->getLabels();
    $ont = $ont_names[$value['ontology-type']];

    // set ontArray and get class
    $ontArray = $this->ontology->getOntology($ont);
    $classes = $this->ontology->getClasses($ontArray);

    // top of form displaying current data

    //top section
    $form['#title'] = $this->t('Content types');
    $form['description'] = array(
      '#type' => 'item',
      '#title' => $this->t('Mapping content type @CT, using the @ont ontology', array('@CT' => $ct, '@ont' => $ont)),
    );

    // class type drop down
    $form['class-type'] = [
      '#title' => $this->t('Class Type'),
      '#description' => $this->t('Select the Ontology Class you want to use'),
      '#type' => 'select',
      '#options' => array_column($classes, 'label'),
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
    // values from first page
    $value = $form_state->get(['page_values', 1]);

    // get ont name
    $ont_names = $this->ontology->getLabels();
    $ont = $ont_names[$value['ontology-type']];

    // set ontArray and get properties
    $ontArray = $this->ontology->getOntology($ont);
    $properties = $this->ontology->getProperties($ontArray);

    $form['property-type'] = [
      '#title' => $this->t('Property Type'),
      '#description' => $this->t('Choose the properties you want to associate'),
      '#type' => 'select',
      '#options' => array_column($properties, 'label'),
      //'#default_value' => $form_state->getValue('rdf-type', ''),
    ];

    $form['contacts'] = array(
      '#type' => 'table',
      '#caption' => $this->t('Sample Table'),
      '#header' => array(
        $this->t('Content Type Field'),
        $this->t('Ontology Property'),
      ),
    );



    for ($i = 1; $i <= 4; $i++){
      $form['contacts'][$i]['name'] = [
        '#type' => 'label',
        '#title' => $i,

      ];
    }

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
    $arr = $this->ontology->getLabels();
    $val = $arr[$value];
    $ont = $this->ontology->getOntology($val);
    $id = $this->ontology->getId($ont);

    drupal_set_message($id);
  }
}
