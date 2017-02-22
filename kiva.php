<?php
  
  /**
   * This file contains bootstrap code for instantiating the KivaTalk class and hiding
   * try-catch complexity from the html code.
   *
   * @author R. Westlund
   * @version 1.0
   * @Copyright © 2017 by R. Westlund. All rights reserved
   */
  
  // include external resources
  require_once('KivaTalk.class.php');
  
  $Kiva = null;
  
  $useTestData = false;
  
  
  // BOOTSTRAP
  
  // let's begin by instantiating our Kiva API wrapper class
  // todo: consider making the class a singleton
  try
  {
    $Kiva = new KivaTalk($useTestData);
  }
  catch (Exception $e)
  {
    // In production errors would go to a log ar get sent to the admin via email;
    // for this challenge, let's just output to the browser
    echo 'Unable to communicate with Kiva API. ' . $e->getMessage();
    exit;
  }
  
  /**
   * Get an associative array of funded loans from the Kiva API.
   *
   * @return array
   */
  function getFundedLoans()
  {
    global $Kiva;
    $loans = array();
    
    try
    {
      $loans = $Kiva->getFundedLoans();
    }
    catch (Exception $e)
    {
      echo __FUNCTION__ . 'failed. ' . $e->getMessage();
    }
    
    return $loans;
  }
  
  /**
   * Build a simple html option fragment for inclusion inside a select menu. For more control over
   * the list (e.g. styles, etc.), you can roll your own by calling getFundedLoans().
   *
   * @param $defautId loan id to initially display in the dropdown
   * @return string the HTML options for a select element
   * @see getFundedLoans()
   */
  function getFundedLoanOptionsHtml($defautId='')
  {
    $optionHtml = '';
    
    if ($loans = getFundedLoans())
    {
      // build the option list
      foreach ($loans as $loan)
      {
        $sel = ($loan['id'] == $defautId ? 'selected' : '');
        $optionHtml .= "<option value='{$loan['id']}' $sel>" . htmlentities($loan['name']) . "</option>\n";
      }
    }
    
    return $optionHtml;
  }
  
  /**
   * Get details for the supplied loan id.
   *
   * @param $loanId
   * @return array
   */
  function getLoanInfo($loanId)
  {
    global $Kiva;
    $loanInfo = array();
    
    try
    {
      $loanInfo = $Kiva->getLoanInfo($loanId);
    }
    catch (Exception $e)
    {
      echo __FUNCTION__ . ' failed. ' . $e->getMessage();
    }
    
    return $loanInfo;
  }
  
  /**
   * Get details for the supplied loan id.
   *
   * @param $loanId
   * @return array
   */
  function getLenders($loanId)
  {
    global $Kiva;
    $lenders = array();
    
    try
    {
      $lenders = $Kiva->getLenders($loanId);
    }
    catch (Exception $e)
    {
      echo __FUNCTION__ . ' failed. ' . $e->getMessage();
    }
    
    return $lenders;
  }
  
  /**
   * Figure out a repayment schedule based on the supplied loan and lender data,
   * and insert the corresponding records in to the database.
   *
   * This function assumes the parameter data come from the same loan.
   *
   * @param $loan
   * @param $lenders
   * @return array SQL statements for repayment schedule
   */
  function buildRepaymentSql($loan, $lenders)
  {
    $sql = array();
    
    // make sure we have everything we need
    if (isset($loan['id']) && isset($loan['terms']['repayment_term']) && isset($loan['loan_amount'])
        && isset($loan['lender_count']) && $loan['lender_count'] > 0
    )
    {
      $loan_id = $loan['id'];
      $totalMonths = $loan['terms']['repayment_term'];
      $totalAmount = $loan['loan_amount'];   // might be any currency (ignored for this challenge)
      $totalLenders = $loan['lender_count'];
      
      // IMPORTANT!
      // todo: can't find amount per lender in the api, so I just divided it evenly (rounded up, final payment might be smaller)
      
      // these steps can introduce rounding errors
      
      $totalPerLender = ceil($totalAmount * 100 / $totalLenders) / 100;
      $monthlyRepaymentAmount = ceil($totalPerLender * 100 / $totalMonths) / 100;
      $finalPayment = $totalPerLender - ($monthlyRepaymentAmount * ($totalMonths - 1));
      
      // let's see how close we get by calculating the discrepancy for each lender
      $discrepancy = $totalPerLender - ($monthlyRepaymentAmount * ($totalMonths - 1) + $finalPayment);
      
      
      // Since I'm distributing the loan amount evenly between the lenders, this loop is not strictly necessary,
      // but I'm using it in order to construct the theoretical repayment schedule and associated table entries.
      
      $sql = array();
      
      foreach ($lenders as $lender)
      {
        // skip any lenders without an id, as they can't be tracked
        if (isset($lender['lender_id']) && !empty($lender['lender_id']))
        {
          $lender_id = $lender['lender_id'];
          
          $sql[] = "INSERT INTO paymentDefaults\n(loan_id, lender_id, default_payment, final_payment, discrepancy) VALUES\n" .
              "($loan_id, $lender_id, $monthlyRepaymentAmount, $finalPayment, $discrepancy)";
  
          // now create the monthly payment SQL statements
          
          // IMPORTANT!
          // The code below assumes the lender_id DOES NOT contain sql code. If this is possible,
          // using PDO_MYSQL (recommended) or mysqli::real_escape_string() will solve the problem.
          
          $s = "INSERT INTO payments\n(loan_id, lender_id, MONTH, amount) VALUES\n";
          
          for ($month = 1; $month < $totalMonths; $month++)
          {
            $s .= "($loan_id, '$lender_id', $month, $monthlyRepaymentAmount),\n";
          }
          
          // payment for final month
          $s .= "($loan_id, '$lender_id', $month, $finalPayment);\n";
          
          // add to our inserts
          $sql[] = $s;
        }
      }
    }
    
    return $sql;
  }
