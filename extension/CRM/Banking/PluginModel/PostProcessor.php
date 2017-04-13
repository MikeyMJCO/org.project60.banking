<?php
/*-------------------------------------------------------+
| Project 60 - CiviBanking                               |
| Copyright (C) 2017 SYSTOPIA                            |
| Author: B. Endres (endres -at- systopia.de)            |
| http://www.systopia.de/                                |
+--------------------------------------------------------+
| This program is released as free software under the    |
| Affero GPL v3 license. You can redistribute it and/or  |
| modify it under the terms of this license which you    |
| can read by viewing the included agpl.txt or online    |
| at www.gnu.org/licenses/agpl.html. Removal of this     |
| copyright header is strictly prohibited without        |
| written permission from the original author(s).        |
+--------------------------------------------------------*/

/**
 *
 * @package org.project60.banking
 * @copyright GNU Affero General Public License
 * $Id$
 *
 */
abstract class CRM_Banking_PluginModel_PostProcessor extends CRM_Banking_PluginModel_Base {

  function __construct($plugin_dao) {
    parent::__construct($plugin_dao);

    // read config, set defaults
    $config = $this->_plugin_config;

    if (!isset($config->contribution_fields_checked)) $config->contribution_fields_checked = 'id,financial_type_id,total_amount';
    if (!isset($config->btx_status_list))             $config->btx_status_list = array('processed');
  }

  /**
   * Postprocess the (already executed) match
   *
   * @param $match    the executed match
   * @param $btx      the related transaction
   * @param $matcher  the matcher plugin executed
   * @param $context  the matcher context contains cache data and context information
   *
   */
  public abstract function processExecutedMatch(CRM_Banking_Matcher_Suggestion $match, CRM_Banking_PluginModel_Matcher $matcher, CRM_Banking_Matcher_Context $context);

  /**
   * Should this postprocessor spring into action?
   * Evaluates the common 'required' fields in the configuration
   *
   * @param $match    the executed match
   * @param $btx      the related transaction
   * @param $context  the matcher context contains cache data and context information
   *
   * @return bool     should the this postprocessor be activated
   */
  protected function shouldExecute(CRM_Banking_Matcher_Suggestion $match, CRM_Banking_PluginModel_Matcher $matcher, CRM_Banking_Matcher_Context $context) {
    // TODO:
    // default criteria: status should be 'processed'

    // TODO: evaluate 'required'
    return TRUE;
  }

  /**
   * Get the ONE contact this transaction has been associated with. If there are
   *  multiple candidates, NULL is returned
   *
   * @param $match    the executed match
   * @param $btx      the related transaction
   * @param $context  the matcher context contains cache data and context information
   *
   * @return int      contact_id of the unique contact linked to the transaction, NULL if not exists/unique
   */
  protected function getSoleContactID(CRM_Banking_Matcher_Suggestion $match, CRM_Banking_PluginModel_Matcher $matcher, CRM_Banking_Matcher_Context $context) {
    // TODO:
  }

  /**
   * Get the list of contributions linked to this trxn ID
   *
   * @param $match    the executed match
   * @param $btx      the related transaction
   * @param $context  the matcher context contains cache data and context information
   *
   * @return array    contribution IDs
   */
  protected function getContributionIDs(CRM_Banking_Matcher_Suggestion $match, CRM_Banking_PluginModel_Matcher $matcher, CRM_Banking_Matcher_Context $context) {
    $contribution_ids = array();

    // get the single-style ('contribution_id')
    $single_id = $match->getParameter('contribution_id');
    if (is_numeric($single_id)) {
      $contribution_ids[$single_id] = 1;
    }

    // get the multi-style ('contribution_ids')
    $multi_ids = $match->getParameter('contribution_ids');
    if (is_array($multi_ids)) {
      foreach ($multi_ids as $contribution_id) {
        if (is_numeric($contribution_id)) {
          $contribution_ids[$contribution_id] = 1;
        }
      }
    }

    return array_keys($contribution_ids);
  }
}

