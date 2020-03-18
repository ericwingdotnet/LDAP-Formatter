<?php

// Configuration settings
error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED);
ini_set('display_errors', 'stderr');


// Define LDAP connection settings
$url = 'ldap://ldap.example.org';
$base = 'o=example.org';

// Create xml document object
$dom = new DOMDocument('1.0','utf-8');

// Create Root Element '<people>'
$people = $dom->appendChild($dom->createElement('people'));

  // Connect to LDAP server
  $ds = ldap_connect($url);
  if ($ds) {

    // Build LDAP filter string
    $fltr = "(&";
    $fltr .= "(ObjectClass=person)";
    $fltr .= "(!(employeetype=applicant))";
    $fltr .= "(|";
    $fltr .= "(ou=sales)";
    $fltr .= "(ou=*support)";
    $fltr .= "(shortouall=marketing)";
    $fltr .= ")";
    $fltr .= ")";

    // Specify LDAP attributes
    $srchAttrs = array(
        'uid',
        'employeenumber',
        'mail',
        'middleinitials',
        'shortou',
        'employeetype',
        'designation',
        'knownas',
        'sn',
        'cn',
        'description'
    );

    // Query LDAP server
    $srch = ldap_search($ds, $base, $fltr, $srchAttrs, 0, 0);
    $personData = ldap_get_entries($ds, $srch);

    // Set attributes for record count and timestamp
    $today = new DateTime();
    $people->setAttribute('count',$personData['count']);
    $people->setAttribute('datetime',$today->format("d-m-Y H:i"));

      // Loop through LDAP records
      for ($i=0; $i<$personData['count']; $i++) {

        // Create Person Element
        $person = $people->appendChild($dom->createElement('person'));

        // Create Child Elements for each attribute field
        for ($k=0; $k<$personData[$i]['count']; $k++) {

          // get attribute name
          $attr = $personData[$i][$k];

          // Handle special characters for XML (e.g. &, <, >, etc.)
          $val = htmlentities($personData[$i][$attr][0], ENT_XML1);

          // replace ';' with '-' for node name from attribute name
          // if it exists in a hidden field (e.g. sn;x-alternate)
          $attr = preg_replace('/;+/','-',$attr);

          // Create and add attribute node to '<person>'
          $node = $dom->createElement($attr,$val);
          $person->appendChild($node);
        }
      }

  // TODO Add proper exception handling
  if ($xmlString = $dom->saveXML()) {

    echo $xmlString;

  } else {

    die("Save Failed.\n");

  }

  // Close LDAP Connection
  ldap_close($ds);

} else {

  die("LDAP Connection Failed.\n");

}
