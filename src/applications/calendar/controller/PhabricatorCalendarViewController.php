<?php

final class PhabricatorCalendarViewController
  extends PhabricatorCalendarController {

  public function shouldAllowPublic() {
    return true;
  }

  public function handleRequest(AphrontRequest $request) {
    $viewer = $this->getViewer();

    $now     = time();
    $request = $this->getRequest();
    $year_d  = phabricator_format_local_time($now, $viewer, 'Y');
    $year    = $request->getInt('year', $year_d);
    $month_d = phabricator_format_local_time($now, $viewer, 'm');
    $month   = $request->getInt('month', $month_d);
    $day   = phabricator_format_local_time($now, $viewer, 'j');


    $holidays = id(new PhabricatorCalendarHoliday())->loadAllWhere(
      'day BETWEEN %s AND %s',
      "{$year}-{$month}-01",
      "{$year}-{$month}-31");

    $statuses = id(new PhabricatorCalendarEventQuery())
      ->setViewer($viewer)
      ->withInvitedPHIDs(array($viewer->getPHID()))
      ->withDateRange(
        strtotime("{$year}-{$month}-01"),
        strtotime("{$year}-{$month}-01 next month"))
      ->execute();

    if ($month == $month_d && $year == $year_d) {
      $month_view = new PHUICalendarMonthView($month, $year, $day);
    } else {
      $month_view = new PHUICalendarMonthView($month, $year);
    }

    $month_view->setBrowseURI($request->getRequestURI());
    $month_view->setUser($viewer);
    $month_view->setHolidays($holidays);
    if ($this->getNoticeView()) {
      $month_view->setInfoView($this->getNoticeView());
    }

    $phids = mpull($statuses, 'getUserPHID');
    $handles = $this->loadViewerHandles($phids);

    foreach ($statuses as $status) {
      $event = new AphrontCalendarEventView();
      $event->setEpochRange($status->getDateFrom(), $status->getDateTo());
      $event->setUserPHID($status->getUserPHID());
      $event->setName($status->getHumanStatus());
      $event->setDescription($status->getDescription());
      $event->setEventID($status->getID());
      $month_view->addEvent($event);
    }

    $date = new DateTime("{$year}-{$month}-01");
    $crumbs = $this->buildApplicationCrumbs();
    $crumbs->addTextCrumb(pht('My Events'));
    $crumbs->addTextCrumb($date->format('F Y'));

    $nav = $this->buildSideNavView();
    $nav->selectFilter('/');
    $nav->appendChild(
      array(
        $crumbs,
        $month_view,
      ));

    return $this->buildApplicationPage(
     $nav,
     array(
        'title' => pht('Calendar'),
      ));
  }

  private function getNoticeView() {
    $request = $this->getRequest();
    $view    = null;

    if ($request->getExists('created')) {
      $view = id(new PHUIInfoView())
        ->setSeverity(PHUIInfoView::SEVERITY_NOTICE)
        ->appendChild(pht('Successfully created your status.'));
    } else if ($request->getExists('updated')) {
      $view = id(new PHUIInfoView())
        ->setSeverity(PHUIInfoView::SEVERITY_NOTICE)
        ->appendChild(pht('Successfully updated your status.'));
    } else if ($request->getExists('deleted')) {
      $view = id(new PHUIInfoView())
        ->setSeverity(PHUIInfoView::SEVERITY_NOTICE)
        ->appendChild(pht('Successfully deleted your status.'));
    } else if (!$request->getUser()->isLoggedIn()) {
      $login_uri = id(new PhutilURI('/auth/start/'))
        ->setQueryParam('next', '/calendar/');
      $view = id(new PHUIInfoView())
        ->setSeverity(PHUIInfoView::SEVERITY_NOTICE)
        ->appendChild(
          pht(
            'You are not logged in. %s to see your calendar events.',
            phutil_tag(
              'a',
              array(
                'href' => $login_uri,
              ),
              pht('Log in'))));
    }

    return $view;
  }

}
