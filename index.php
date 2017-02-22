<html>
<head>
  <link rel="stylesheet" type="text/css" href="main.css">
  
  <title>Kiva Coding Challenge</title>
</head>
<body>

<?php
  /**
   * Coding Challenge from Kiva (as described in the email)
   * 
   * Start by taking a look at the data we make available via our public API http://build.kiva.org/api
   * 
   * --------
   * Task 1
   * --------
   * 
   * With this information create a script (using any language you feel most comfortable in) that queries 
   * the API for funded status loans, e.g. http://api.kivaws.org/v1/loans/search.json?status=funded
   * 
   * Choose a loan from the list and pull its information, e.g. http://api.kivaws.org/v1/loans/300000.json
   * 
   * Then, also pull a list of that loan’s lenders, e.g http://api.kivaws.org/v1/loans/300000/lenders.json
   * 
   * There is more background information on the API and its use on build.kiva.org as well as at https://github.com/kiva/API
   * 
   * --------
   * Task 2
   * --------
   * 
   * Use the loan information you gathered above ( e.g:  "loan_amount":100 (in USD), "repayment_term":7 (in months)) 
   * to build out a loan repayment schedule into a database table.
   * 
   * Then use the list of lenders and determine an estimated repayment schedule for each one. Create a database schema 
   * to hold this information and a script that distributes the repayments equally across the lenders. This creates an 
   * audit trail that each lender received back the amount they put into the loan.
   * 
   * Example, Kevin and Nina are two lenders to a loan for $100 and each purchased $50 each and they should both be 
   * repaid that amount over the course of the repayment schedule (e.g. 7 months).
   * 
   * Then, write unit tests for the code as well as an integrity test for the data that ensures each lender got back 
   * their expected amount over the course of the loan.
   *
   * There is no requirement on using a certain language and including frameworks/libraries is fine. Use the best tool for you.
   * 
   * @author R. Westlund
   * @version 1.0
   * @Copyright © 2017 R. Westlund. All rights reserved.
   */

  require_once('kiva.php');
  
  define('TEST_LOAN_ID', 1237937);
  
  $loanId = ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_POST['loan_id']) ? $_POST['loan_id'] : TEST_LOAN_ID);
?>

<form name="criteria" method="post">
  <label for="loan_id">Select funded loan:</label>
  <select id="loan_id" name="loan_id">
    <option value="">(test loan)</option>
    <?php echo getFundedLoanOptionsHtml($loanId); ?>
  </select>
  <input type="submit" name="submit" value="Submit" />
</form>

<h3>MySQL statements for loan <?php echo $loanId; ?></h3>
  
<pre style="pre-wrap">
  <?php $loan = getLoanInfo($loanId); /*print_r($loan);*/ ?>

  <?php $lenders = getLenders($loanId); /*print_r($lenders);*/ ?>

  <?php 
    $sql = buildRepaymentSql($loan, $lenders); 
    //echo "\n", file_get_contents('schema.sql');
    foreach ($sql as $s) echo "\n", $s;
  ?>
</pre>

</body>
</html>
