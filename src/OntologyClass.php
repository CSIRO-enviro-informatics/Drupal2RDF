<?php

namespace Drupal\semantic_map;
use Symfony\Component\Yaml\Yaml;

class OntologyClass{

  $yaml = new Yaml();
  $ontologies_file = __DIR__ . '/../resources/yaml/ontologies.yml';
  $ontologies_array = $yaml->parse(file_get_contents($ontologies_file));

  // takes in a String and returns a single ontology array
  public function getOntology(String $key){
    $ontology = global $ontologies_array[$key];
    return $ontology;
  }

  // returns an array of all labels
  // pass in $ontologies_file, $ontologies_array, classes or properties
  public function getLabel(array $arr){
    $label = array_column($arr, 'label');
    return $label;
  }

  // returns an array of all id's
  // pass in $ontologies_file or $ontologies_array
  public function getId(array $arr){
    $Id = array_column($arr, 'id');
    return $Id;
  }

  // returns an array of all descriptions
  // pass in $ontologies_file, $ontologies_array, classes or properties
  public function getDescription(array $arr){
    $description = array_column($arr, 'description');
    return $description;
  }

  // returns an array of all classes for given ontology
  public function getClasses(array $arr){
    $key = $arr['id'];
    $data = __DIR__ . '/../resources/onts_yaml/' . $filename . '_classes.yml';
    $classes = $yaml->parse(file_get_contents($data));

    $return classes;
  }

  // returns an array of all properties for given ontology
  public function getProperties(array $arr){
    $key = $arr['id'];
    $data = __DIR__ . '/../resources/onts_yaml/' . $filename . '_properties.yml';
    $properties = $yaml->parse(file_get_contents($data));

    $return properties;
  }

}

?>
