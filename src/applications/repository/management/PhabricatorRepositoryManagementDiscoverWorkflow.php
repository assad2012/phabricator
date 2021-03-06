<?php

final class PhabricatorRepositoryManagementDiscoverWorkflow
  extends PhabricatorRepositoryManagementWorkflow {

  protected function didConstruct() {
    $this
      ->setName('discover')
      ->setExamples('**discover** [__options__] __repository__ ...')
      ->setSynopsis('Discover __repository__, named by callsign.')
      ->setArguments(
        array(
          array(
            'name'        => 'verbose',
            'help'        => 'Show additional debugging information.',
          ),
          array(
            'name'        => 'repair',
            'help'        => 'Repair a repository with gaps in commit '.
                             'history.',
          ),
          array(
            'name'        => 'repos',
            'wildcard'    => true,
          ),
        ));
  }

  public function execute(PhutilArgumentParser $args) {
    $repos = $this->loadRepositories($args, 'repos');

    if (!$repos) {
      throw new PhutilArgumentUsageException(
        'Specify one or more repositories to discover, by callsign.');
    }

    $console = PhutilConsole::getConsole();
    foreach ($repos as $repo) {
      $console->writeOut("Discovering '%s'...\n", $repo->getCallsign());

      id(new PhabricatorRepositoryDiscoveryEngine())
        ->setRepository($repo)
        ->setVerbose($args->getArg('verbose'))
        ->setRepairMode($args->getArg('repair'))
        ->discoverCommits();
    }

    $console->writeOut("Done.\n");

    return 0;
  }

}
