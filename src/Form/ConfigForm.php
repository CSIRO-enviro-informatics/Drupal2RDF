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

  // ontologyClass for functions and ontology array
  public $ontology; // OntologyClass object
  public $ontology_array; // Array of all Ontologies, with id, label, and desc.

  // ontology arrays and variables needed
  public $ontology_labels; // array of ontology labels used for lookup

  // user chosen data
  public $chosen_ontology; // user chosen ontology array[id, label, desc]
  public $chosen_ontology_classes; // list of classes of a given ontology label
  public $chosen_ontology_properties;
  public $chosen_ontology_class; //
  public $chosen_ontology_label; // user chosen ontology label

  public $content_types; // 1D array of content type labels
  public $chosen_content_type_label; // user chosen content type label
  public $chosen_content_type_machine; // user chosen content type machine name
  public $chosen_content_type_fields; // fields given a user chosen content type

  // constructor
  public function __construct(){
    $this->ontology = new OntologyClass();
    $this->ontology_array = $this->ontology->getArray();
    $this->ontology_labels = $this->ontology->getLabels();
    $this->content_types = node_type_get_names();
  }

  // handles the "next" button.
  public function nextSubmit(array &$form, FormStateInterface &$form_state) {
    $pageNum = $form_state->get('page_num');
    $prevPage = $pageNum-1;
    $nextPage = $pageNum+1;

    // save form state
    $form_state->set(['page_values', $pageNum], $form_state->getValues());

    if ($form_state->has(['page_values', $nextPage])) {
      $form_state->setValues($form_state->get(['page_values', $nextPage]));
    }

    // When form rebuilds, build method would be chosen based on to page_num.
    $form_state->set('page_num', $nextPage);
    $form_state->setRebuild();
  }

  // handles the "back" button. // NEEDS FIXING
  public function backSubmit(array &$form, FormStateInterface &$form_state) {
    $pageNum = $form_state->get('page_num');
    $prevPage = $pageNum-1;
    $nextPage = $pageNum+1;

    // save form state
    $form_state->set(['page_values', $pageNum], $form_state->getValues());

    if ($form_state->has(['page_values', $prevPage])) {
      $form_state->setValues($form_state->get(['page_values', $prevPage]));
    }

    // When form rebuilds, build method would be chosen based on to page_num.
    $form_state->set('page_num', $prevPage);
    $form_state->setRebuild();
  }

  public function getFormId(){
    return 'config_form';
  }

  // build form page 1
  public function buildForm(array $form, FormStateInterface $form_state){

    //$testData = $this->getEntityMappings('article');
    //var_dump($testData);

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
      '#options' => $this->content_types,
    ];

    // ontology type drop down
    $form['ontology-type'] = [
      '#title' => $this->t('Ontology'),
      '#description' => $this->t('Select the Ontology you want to use'),
      '#type' => 'select',
      '#required' => TRUE,
      '#options' => $this->ontology_labels,
    ];

    // next button
    $form['actions'] = array('#type' => 'actions');
    $form['actions']['next'] = [
      '#type' => 'submit',
      '#value' => $this->t('Next >'),
      '#button_type' => 'primary',
      '#submit' => array(array($this, 'nextSubmit')),
      '#validate' => array(array($this, 'nextValidate')),
    ];

    return $form;
  }

  // build form page 2
  protected function buildFormPageTwo(array $form, FormStateInterface $form_state){
    // values of first page
    $value = $form_state->get(['page_values', 1]);

    // set global data (chosen ont label, ont array, ont classes, properties, ct types)
    $this->chosen_ontology_label = $this->ontology_labels[$value['ontology-type']];
    $this->chosen_ontology = $this->ontology->getOntology($this->chosen_ontology_label);
    $this->chosen_ontology_classes = $this->ontology->getClasses($this->chosen_ontology);
    $this->chosen_ontology_properties = $this->ontology->getProperties($this->chosen_ontology);

    $this->chosen_content_type_label = $this->content_types[$value['content-type']];
    $this->chosen_content_type_machine = $value['content-type'];
    $this->chosen_content_type_fields = $this->getFields($this->chosen_content_type_machine);

    var_dump($this->chosen_ontology);
    var_dump($this->ontology->getLabels());

    // top of form displaying current data
    $form['#title'] = $this->t('Semantic Map');
    $form['description'] = array(
      '#type' => 'item',
      '#title' => $this->t('Selected: '),
      '#description' =>
      $this->t('Content Type: @CT', array('@CT' => $this->chosen_content_type_label)) . '<br>' .
      $this->t('Ontology: @ont', array('@ont' => $this->chosen_ontology_label)),
    );

    // class type drop down
    $form['class-type'] = [
      '#title' => $this->t(' "@ont" Class Types', array('@ont' => $this->chosen_ontology_label)),
      '#description' => $this->t('Select the Ontology Class you want to use'),
      '#type' => 'select',
      '#options' => array_column($this->chosen_ontology_classes, 'label'),
        //'#default_value' => $form_state->getValue('rdf-type', ''),
    ];

    // next button
    $form['actions'] = array('#type' => 'actions');
    $form['actions']['next'] = [
      '#type' => 'submit',
      '#value' => $this->t('Next >'),
      '#button_type' => 'primary',
      '#submit' => array(array($this, 'nextSubmit')),
      '#validate' => array(array($this, 'nextValidate')),
    ];

    // back submit
    $form['actions']['back'] = [
      '#type' => 'submit',
      '#value' => $this->t('< Back'),
      '#submit' => array(array($this, 'backSubmit')),
      '#validate' => array(array($this, 'backValidate')),
      '#weight' => -1,
    ];


    return $form;
  }

  protected function buildFormPageThree(array $form, FormStateInterface $form_state){
    // values from 2nd page
    $value = $form_state->get(['page_values', 2]);

    // local array of class labels to set global chosen class
    $classList = array_column($this->chosen_ontology_classes, 'label');
    $this->chosen_ontology_class = $classList[$value['class-type']];

    var_dump($this->chosen_ontology_class);

    // set ontArray and get properties
    $property_labels = array_column($this->chosen_ontology_properties, 'label');
    $property_descriptions = array_column($this->chosen_ontology_properties, 'description');

    // top section
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

    // Next, loop through the $ct_fields array, array.length times,
    // add select dropdown of ont per ct_field, and enable box.
    // creates an associative array for each element
    foreach ($this->chosen_content_type_fields as $field) {
      $table[$field] = array(
        'enable' => array(
          '#type' => 'checkbox',
          '#title' => $this->t('Enable'),
          '#title_display' => 'invisible',
        ),
        'ct_field' => array(
          '#markup' => '<b>' . $field . '</b>' . '<br>' . $field . $this->t(' description') . '</br>',
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

    // next button
    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#button_type' => 'primary',
      //'#submit' => array(array($this, 'nextSubmit')),
      //'#validate' => array(array($this, 'nextValidate')),

      //NEED TO ADD SAVE SUBMIT AND SAVE VALIDATE
    ];

    // back submit
    $form['actions']['back'] = [
      '#type' => 'submit',
      '#value' => $this->t('< Back'),
      '#submit' => array(array($this, 'backSubmit')),
      '#validate' => array(array($this, 'backValidate')),
      '#weight' => -1,
    ];

    return $form;
  }

  // returns fields for a given content-type (string)(ct_machineName)
  // THIS CAN BE MORE EFFICIENT. BUNDLE SHOULD = BUNDLEFIELDS I THINK

  // THE ANSWER IS HERE FOR SAVING AND HANDLING THE DATA! NODE AND BUNDLE! USE MACHINE_NAME
  // entityManager must be changed to entityTypeManager
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

  public function getEntityMappings(string $bundle){
    $entityManager = \Drupal::service('entity_field.manager');
    $entity_type_id = 'node';
    $mappings = rdf_get_mapping($entity_type_id, $bundle);

    return $mappings;
  }



  public function nextValidate(array $form, FormStateInterface $form_state) {
  // @TODO validate if required.
  }

  public function backValidate(array $form, FormStateInterface $form_state) {
  // @TODO validate if required.
  }


  // THIS IS TESTING ONLY ATM
  public function submitForm(array &$form, FormStateInterface $form_state){

    // page 1 variables
    $page_one_values = $form_state->get(['page_values', 1]);
    $content_type = $page_one_values['content-type'];
    $ontology_type = $page_one_values['ontology-type'];

    // ct_machine name
    $ct_names = node_type_get_names();
    $ct_name = $ct_names[$page_one_values['content-type']];
    $ct_machine = $page_one_values['content-type'];

    $ont_names = $this->ontology->getLabels();
    $ont = $ont_names[$page_one_values['ontology-type']];

    //page 2 variables
    $page_two_values = $form_state->get(['page_values', 2]);

    $ontArray = $this->ontology->getOntology($ont);
    $classes = $this->ontology->getClasses($ontArray);
    $ontology_classes = array_column($classes, 'label');

    $ontology_class = $ontology_classes[$page_two_values['class-type']];

    // page 3 variables

    // DELETE ALL ABOVE??





    // get mappings, given content type ct_machine
    $mappings = $this->getEntityMappings($ct_machine);

    $message = $this->t($this->chosen_ontology['id'] . ":" . $this->chosen_ontology_class);

    //$mappings->setBundleMapping(array('types' => array($ontology_class)))->save();

    // NEEDS UPDATE TO CHECK FOR name
    // check name, if no name, add schema:name

    // Add mapping for title field.



    // iterate through enabled properties, and append them to $ontology_properties


    //drupal_set_message(print_r($message, TRUE));
    drupal_set_message($message);
  }
}
