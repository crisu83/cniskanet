<?php

/**
 * @file
 * Page callbacks for the Poll module.
 */

/**
 * Page callback: Displays a simple list of all available polls.
 *
 * @return
 *   The HTML for the page that shows the available polls.
 *
 * @see poll_menu()
 */
function poll_page() {
  $polls_per_page = 15;

  $count_select = db_select('node', 'n');
  $count_select->addExpression('COUNT(*)', 'expression');
  $count_select->join('poll', 'p', 'p.nid = n.nid');
  $count_select->condition('n.status', 1);

  // List all polls.
  $select = db_select('node', 'n');
  $select->join('poll', 'p', 'p.nid = n.nid');
  $select->join('poll_choice', 'c', 'c.nid = n.nid');
  $select->addExpression('SUM(c.chvotes)', 'votes');
  $select = $select->fields('n', array('nid', 'title', 'created'))
    ->fields('p', array('active'))
    ->condition('n.status', 1)
    ->orderBy('n.created', 'DESC')
    ->groupBy('n.nid')
    ->groupBy('n.title')
    ->groupBy('p.active')
    ->groupBy('n.created')
    ->extend('Drupal\Core\Database\Query\PagerSelectExtender')
    ->limit($polls_per_page)
    ->addTag('node_access');
  $select->setCountQuery($count_select);
  $queried_nodes = $select->execute()
    ->fetchAllAssoc('nid');

  $output = '<ul>';
  // Do not use $node->label() here because $node comes from the database.
  foreach ($queried_nodes as $node) {
    $output .= '<li>' . l($node->title, "node/$node->nid") . ' - ' . format_plural($node->votes, '1 vote', '@count votes') . ' - ' . ($node->active ? t('open') : t('closed')) . '</li>';
  }
  $output .= '</ul>';
  $output .= theme('pager');
  return $output;
}

/**
 * Page callback: Displays the 'votes' tab for polls.
 *
 * This page displays a table containing each vote that has been cast.
 *
 * @param $node
 *   The poll node object.
 *
 * @return
 *   Render array containing table with votes.
 *
 * @see poll_menu()
 */
function poll_votes($node) {
  $votes_per_page = 20;
  drupal_set_title($node->label());

  $header[] = array('data' => t('Visitor'), 'field' => 'u.name');
  $header[] = array('data' => t('Vote'), 'field' => 'pc.chtext');
  $header[] = array('data' => t('Timestamp'), 'field' => 'pv.timestamp', 'sort' => 'desc');

  $select = db_select('poll_vote', 'pv')
    ->extend('Drupal\Core\Database\Query\PagerSelectExtender')
    ->extend('Drupal\Core\Database\Query\TableSortExtender');
  $select->join('poll_choice', 'pc', 'pv.chid = pc.chid');
  $select->join('users', 'u', 'pv.uid = u.uid');
  $queried_votes = $select
    ->addTag('translatable')
    ->fields('pv', array('chid', 'uid', 'hostname', 'timestamp', 'nid'))
    ->fields('pc', array('chtext'))
    ->fields('u', array('name'))
    ->condition('pv.nid', $node->nid)
    ->limit($votes_per_page)
    ->orderByHeader($header)
    ->execute();

  $rows = array();
  foreach ($queried_votes as $vote) {
    $rows[] = array(
      $vote->name ? theme('username', array('account' => $vote)) : check_plain($vote->hostname),
      check_plain($vote->chtext),
      format_date($vote->timestamp),
    );
  }
  $build['poll_votes_table'] = array(
    '#theme' => 'table',
    '#header' => $header,
    '#rows' => $rows,
    '#prefix' => t('This table lists all the recorded votes for this poll. If anonymous users are allowed to vote, they will be identified by the IP address of the computer they used when they voted.'),
  );
  $build['poll_votes_pager'] = array('#theme' => 'pager');
  return $build;
}

/**
 * Page callback: Displays the 'results' tab for the current poll.
 *
 * This tab displays a summary of the votes that have been cast.
 *
 * @param $node
 *   The poll node object.
 *
 * @return
 *   An array suitable for use by drupal_render().
 *
 * @see poll_menu()
 */
function poll_results($node) {
  drupal_set_title($node->label());
  $node->show_results = TRUE;
  return node_show($node);
}
