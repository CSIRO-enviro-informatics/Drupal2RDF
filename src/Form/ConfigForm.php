<?php

namespace Drupal\semantic_map\Form;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Symfony\Component\Yaml\Yaml;
use Drupal\semantic_map\OntologyClass;
use Drupal\node\Entity\Node;
use Drupal\field\FieldConfigInterface;

class ConfigForm extends FormBase{

  public $ontology;
  public $ontology_array;

  public function __construct(){
    $this->ontology = new OntologyClass();
    $this->ontology_array = $this->ontology->getArray();
  }

  // handles the "next" button. // NEEDS FIXING
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

    $form['#title'] = $this->t('Semantic Map');
    $form['description'] = array(
      '#type' => 'item',
      '#title' => $this->t('Choose your Content Type and your Ontology'),
    );

    // content type drop down
    $form['content-type'] = [
      '#title' => $this->t('Content Type'),
      '#description' => $this->t('Select the Content Type you want to begin mapping to'),
      '#type' => 'select',
      '#required' => TRUE,
      '#options' => node_type_get_names(),
    ];

    // ontology type drop down
    $form['ontology-type'] = [
      '#title' => $this->t('Ontology'),
      '#description' => $this->t('Select the Ontology you want to use'),
      '#type' => 'select',
      '#required' => TRUE,
      '#options' => $this->ontology->getLabels(),
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

/*
    // DEBUGGING PURPOSES ONLy
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Calculate'),
    ];
*/

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
    $form['#title'] = $this->t('Semantic Map');
    $form['description'] = array(
      '#type' => 'item',
      '#title' => $this->t('Selected: '),
      '#description' =>
      $this->t('Content Type: @CT', array('@CT' => $ct)) . '<br>' .
      $this->t('Ontology: @ont', array('@ont' => $ont)),
    );

    // class type drop down
    $form['class-type'] = [
      '#title' => $this->t(' "@ont" Class Types', array('@ont' => $ont)),
      '#description' => $this->t('Select the Ontology Class you want to use'),
      '#type' => 'checkboxes',
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

    // get CT type names list, name, machineName and Fields
    $ct_names = node_type_get_names();
    $ct_name = $ct_names[$value['content-type']];
    $ct_machine = $value['content-type'];
    $ct_fields = $this->getFields($ct_machine);

    // get ont name
    $ont_names = $this->ontology->getLabels();
    $ont = $ont_names[$value['ontology-type']];

    // set ontArray and get properties
    $ontArray = $this->ontology->getOntology($ont);
    $properties = $this->ontology->getProperties($ontArray);
    $property_labels = array_column($properties, 'label');

    $form['#title'] = $this->t('Semantic Map');
    $form['description'] = array(
      '#type' => 'item',
      '#title' => $this->t('Choose fields and properties to be mapped to each other'),
    );

    // init table associative array and headers array
    $table = array(
      '#type' => 'table',
      '#header' => array(
        'enable' => $this->t('Enable'),
        'ct_field' => $this->t('Content Type Field'),
        'ont_property' => $this->t('Ontology Property'),
      ),
    );

    // Next, loop through the $ct_fields array array.length times,
    // add select dropdown of ont per ct_field, and enable box.
    // creates an associative array for each element
    foreach ($ct_fields as $field) {
      $table[$field] = array(
        'enable' => array(
          '#type' => 'checkbox',
          '#title' => $this->t('Enable'),
          '#title_display' => 'invisible',
        ),
        'ct_field' => array(
          '#markup' => '<b>' . $field . '</b>' . '<br>' . $field .  $this->t(' description') . '</br>',
          '#description' => $this->t('Select the Ontology Class you want to use'),
        ),
        'ont_property' => array(
          '#type' => 'select',
          '#title_display' => 'invisible',
          '#title' => $this->t('Ontology Properties'),
          '#options' => $property_labels,
          '#empty_option' => $this->t('- Select a field type -'),
        ),
      );
    }

    // set form to table
    $form['fields'] = $table;

    return $form;
  }

  // returns fields for a given content-type (string)(ct_machineName)
  // THIS CAN BE MORE EFFICIENT. BUNDLE SHOULD = BUNDLEFIELDS I THINK
  public function getFields(string $bundle){
    $entityManager = \Drupal::service('entity_field.manager');
    $entity_type_id = 'node';

    foreach ($entityManager->getFieldDefinitions($entity_type_id, $bundle) as $field_name => $field_definition) {
      if (!empty($field_definition->getTargetBundle())) {
        $bundleFields[$entity_type_id][$field_name]['type'] = $field_definition->getType();
        $bundleFields[$entity_type_id][$field_name]['label'] = $field_definition->getLabel();
      }
    }

    // filter to only the editable fields
    $node = $bundleFields;
    $newArr = [];
    foreach ($node['node'] as $key){
      $newArr[] = $key['label'];
    }

    return $newArr;
  }

  public function nextValidate(array $form, FormStateInterface $form_state) {
  // @TODO validate if required.
  }

  public function pageTwoBackValidate(array $form, FormStateInterface $form_state) {
  // @TODO validate if required.
  }


  // THIS IS TESTING ONLY ATM
  public function submitForm(array &$form, FormStateInterface $form_state){
    $value = $form_state->getValue('ontology-type' , '#options');
    $arr = $this->ontology->getLabels();
    $val = $arr[$value];
    $ont = $this->ontology->getOntology($val);
    $id = $this->ontology->getId($ont);

    drupal_set_message($id);
  }
}
