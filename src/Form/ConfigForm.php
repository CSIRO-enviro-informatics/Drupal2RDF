<?php

namespace Drupal\semantic_map\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\Yaml\Yaml;

class ConfigForm extends FormBase{

  public function getFormId(){
    return 'config_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['content-type'] = [
      '#title' => $this->t('Content Type'),
      '#description' => $this->t('Specify the Content Type you want to begin mapping to'),
      '#type' => 'select',
      '#options' => node_type_get_names(),
        //'#default_value' => $form_state->getValue('rdf-type', ''),
    ];

    $data = __DIR__ . '/../../resources/yaml/classes.yml';
    $yaml = new Yaml();
    $dump = $yaml->parse(file_get_contents($data));

    var_dump($dump);

        /*
        // Otherwise build page 1.
        $form_state->set('page_num', 1);

        $form['#title'] = $this->t('Content types');
        $form['description'] = array(
            '#type' => 'item',
            '#title' => $this->t('Create a content type by importing Schema.Org entity type.'),
        );
        */


        /*
        $form['actions'] = array('#type' => 'actions');
        $form['actions']['next'] = array(
            '#type' => 'submit',
            '#value' => $this->t('Next >>'),
            '#button_type' => 'primary',
            '#submit' => array(array($this, 'nextSubmit')),
            '#validate' => array(array($this, 'nextValidate')),
        );

        */
    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state){
    drupal_set_message($form_state->getValue('content-type'));
  }
}
