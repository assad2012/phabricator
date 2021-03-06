<?php

final class AlmanacInterfaceDatasource
  extends PhabricatorTypeaheadDatasource {

  public function isBrowsable() {
    // TODO: We should make this browsable, but need to make the result set
    // orderable by device name.
    return false;
  }

  public function getPlaceholderText() {
    return pht('Type an interface name...');
  }

  public function getDatasourceApplicationClass() {
    return 'PhabricatorAlmanacApplication';
  }

  public function loadResults() {
    $viewer = $this->getViewer();
    $raw_query = $this->getRawQuery();

    $devices = id(new AlmanacDeviceQuery())
      ->setViewer($viewer)
      ->withNamePrefix($raw_query)
      ->execute();

    if ($devices) {
      $interfaces = id(new AlmanacInterfaceQuery())
        ->setViewer($viewer)
        ->withDevicePHIDs(mpull($devices, 'getPHID'))
        ->execute();
    } else {
      $interfaces = array();
    }

    if ($interfaces) {
      $handles = id(new PhabricatorHandleQuery())
        ->setViewer($viewer)
        ->withPHIDs(mpull($interfaces, 'getPHID'))
        ->execute();
    } else {
      $handles = array();
    }

    $results = array();
    foreach ($handles as $handle) {
      $results[] = id(new PhabricatorTypeaheadResult())
        ->setName($handle->getName())
        ->setPHID($handle->getPHID());
    }

    return $results;
  }

}
