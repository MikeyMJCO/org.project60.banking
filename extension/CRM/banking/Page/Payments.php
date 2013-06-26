<?php
/*
    org.project60.banking extension for CiviCRM

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU Affero General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU Affero General Public License for more details.

    You should have received a copy of the GNU Affero General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/
    
require_once 'CRM/Core/Page.php';
require_once 'CRM/Banking/Helpers/OptionValue.php';

class CRM_Banking_Page_Payments extends CRM_Core_Page {
  function run() {
    // Example: Set the page-title dynamically; alternatively, declare a static title in xml/Menu/*.xml
    CRM_Utils_System::setTitle(ts('Payments'));

    // look up the payment states
    $payment_states = banking_helper_optiongroup_id_name_mapping('civicrm_banking.bank_tx_status');

    if (isset($_REQUEST['show']) && $_REQUEST['show']=="statements") {
        // read all batches
        $params = array('version' => 3);
        $result = civicrm_api('BankingTransactionBatch', 'get', $params);
        $statement_rows = array();
        foreach ($result['values'] as $entry) {
            $info = $this->investigate($entry['id'], $payment_states);
            array_push($statement_rows,
                array(  
                        'id' => $entry['reference'], 
                        'date' => $entry['starting_date'], 
                        'count' => $entry['tx_count'], 
                        'target' => $info['target_account'],
                        'analysed' => $info['analysed'].'%',
                        'completed' => $info['completed'].'%',
                    )
            );
        }

        $this->assign('rows', $statement_rows);
        $this->assign('status_message', sizeof($statement_rows).' incomplete statements.');
        $this->assign('show', 'statements');        


    } else {
        // read all transactions
        $params = array('version' => 3);
        $result = civicrm_api('BankingTransaction', 'get', $params);
        $payment_rows = array();
        foreach ($result['values'] as $entry) {
            $status = $payment_states[$entry['status_id']]['label'];
            array_push($payment_rows, 
                array(  
                        'id' => $entry['id'], 
                        'date' => $entry['value_date'], 
                        'amount' => (isset($entry['amount'])?$entry['amount']:"unknown"), 
                        'account_owner' => 'TODO', 
                        'source' => (isset($entry['party_ba_id'])?$entry['party_ba_id']:"unknown"),
                        'target' => (isset($entry['ba_id'])?$entry['ba_id']:"unknown"),
                        'state' => $status,
                        'url_link' => CRM_Utils_System::url('civicrm/banking/review', 'id='.$entry['id']),
                    )
            );
        }

        $this->assign('rows', $payment_rows);
        $this->assign('status_message', sizeof($payment_rows).' unprocessed payments.');
        $this->assign('show', 'payments');        
    }

    // URLs
    $this->assign('url_show_payments', CRM_Utils_System::url('civicrm/banking/payments', 'show=payments'));
    $this->assign('url_show_statements', CRM_Utils_System::url('civicrm/banking/payments', 'show=statements'));
    $this->assign('url_show_all', CRM_Utils_System::url('civicrm/banking/review', sprintf('id=%d&list=%s', $entry['id'], 'all')));

    parent::run();
  }

  /**
   * will iterate through all transactions in the given statements and
   * return an array with some further information:
   *   'analysed'      => percentage of analysed statements
   *   'completed'      => percentage of completed statements
   *   'target_account' => the target account
   */
  function investigate($stmt_id, $payment_states) {
    // go over all transactions to find out rates and data
    $target_account = "Unknown";
    $analysed_state_id = $payment_states['suggestions']['id'];
    $analysed_count = 0;
    $completed_state_id = $payment_states['processed']['id'];
    $completed_count = 0;
    $count = 0;


    $btx_query = array('version' => 3, 'tx_batch_id' => $stmt_id);
    $btx_result = civicrm_api('BankingTransaction', 'get', $btx_query);
    foreach ($btx_result['values'] as $btx) {
        $count += 1.0;
        if (isset($btx['ba_id']))
            $target_account = $btx['ba_id'];

        if ($btx['status_id']==$completed_state_id) {
            $completed_count += 1;
        } else if ($btx['status_id']==$analysed_state_id) {
            $analysed_count += 1;
        }
    }
    
    if ($count) {
      return array(
        'analysed'       => ($analysed_count / $count * 100.0),
        'completed'      => ($completed_count / $count * 100.0),
        'target_account' => $target_account
        );
    } else {
      return array(
        'analysed'       => 0,
        'completed'      => 0,
        'target_account' => $target_account
        );
    }
  }
}
