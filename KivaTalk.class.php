<?php
  
  /**
   * This class talks to the Kiva API using CURL. The constructor throws an exception
   * if CURL is not enabled.
   * 
   * @author R. Westlund
   * @version 1.0
   * @Copyright © 2017 by R. Westlund. All rights reserved
   */
  class KivaTalk
  {
    /** set true to use test data regardless of class property */
    const FORCE_TEST_MODE = false;
    
    /** base url for API requests */
    const API_URL = 'http://api.kivaws.org/v1/';
    
    /** @var bool true when using test data */
    protected $testMode = false;
    
    /** @var array of loan data */
    protected $loans = null;
    
    
    /**
     * KivaTalk constructor. Initialize the class.
     *
     * @param bool $testMode set true to use test data (i.e. don't make API calls); default false
     * @throws Exception
     */
    public function __construct($testMode=false)
    {
      // set testing mode
      $this->testMode = (self::FORCE_TEST_MODE || $testMode);
      
      // we use curl, make sure it's enabled
      if (!function_exists('curl_version')) throw new Exception('This solution requires enabling the CURL library.');
    }
  
    /**
     * Get the loan data for all funded loans.
     * 
     * @return array funded loans
     */
    public function getFundedLoans()
    {
      // for this project, in order to reduce unnecessary API calls, let's cache this locally
      if (is_null($this->loans))
      {
        // get the API data and format for use in PHP
        if ($this->testMode)
        {
          $result = $this->processApiResponse($this->getTestData(__FUNCTION__));
        }
        else
        {
          $result = $this->apiCall('loans/search.json?status=funded');
        }
        
        // get the loan portion of the response
        $loans = (isset($result['loans']) && is_array($result['loans']) ? $result['loans'] : null);
        
        // cache the loan data in a class property
        $this->loans = $loans;
      }
      
      return $this->loans;
    }
  
    /**
     * Get information for the supplied loan id.
     *
     * @param $loanId
     * @return array
     * @throws Exception
     */
    public function getLoanInfo($loanId)
    {
      if (!is_numeric($loanId)) throw new Exception('loan id must be numeric');
  
      // get the API data and format for use in PHP
      if ($this->testMode)
      {
        $result = $this->processApiResponse($this->getTestData(__FUNCTION__));
      }
      else
      {
        $result = $this->apiCall("loans/$loanId.json");
      }
      
      // grab the loan portion of the response
      $loans = (isset($result['loans']) ? $result['loans'] : null);
      
      // the API call always returns an array, but since we are only interested in a single loan, just return the first element
      return (is_array($loans) && isset($loans[0]) ? $loans[0] : null);
    }
  
    /**
     * Get lender information for the supplied loan id.
     *
     * @param $loanId
     * @return array
     * @throws Exception
     */
    public function getLenders($loanId)
    {
      if (!is_numeric($loanId)) throw new Exception('loan id must be numeric');
  
      // get the API data and format for use in PHP
      if ($this->testMode)
      {
        $result = $this->processApiResponse($this->getTestData(__FUNCTION__));
      }
      else
      {
        $result = $this->apiCall("loans/$loanId/lenders.json");
      }
  
      // grab the lender portion of the response
      $lenders = (isset($result['lenders']) && is_array($result['lenders']) ? $result['lenders'] : array());
  
      return $lenders;
    }
  
    /**
     * Call the API and return the resulting JSON-encoded string
     * 
     * @param string $command The API call to make (do not prepend a slash)
     * @return string return the call result (JSON string)
     * @throws Exception
     */
    protected function apiCall($command)
    {
      // build the URL
      // todo: class currently assumes programmer has supplied a valid and properly-formatted api request
      $url = self::API_URL . $command;
      
      //echo "Calling api with: $url\n";
  
      //-------------------------
      // Call the API
      //-------------------------
      
      // todo: since we're not looking at the headers, consider using file_get_contents() instead, or using it as a fallback
      
      // prep for the call
      $curl = curl_init($url);
      curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
      
      // make the call
      $curl_response = curl_exec($curl);
      
      // to grab response for offline testing...
      // [Note: If running from browser instead of command-line, use htmlentities(var_export($curl_response))]
      //echo "\ncurl response is: "; var_export($curl_response); echo "\n";
      
      // check for error
      if ($curl_response === false)
      {
        $info = curl_getinfo($curl);
        
        curl_close($curl);
        
        throw new Exception('curl failed: ' . print_r($info, true));
      }
      
      curl_close($curl);
  
      //-------------------------
      // Parse the response
      //-------------------------
  
      // parse the result and check for errors
      $decoded = $this->processApiResponse($curl_response);
      
      return $decoded;
    }
  
    /**
     * Decode a Kiva JSON response string and check for errors.
     * 
     * @param $json string json-encoded API response (or test data)
     * @return array associative array of response data
     * @throws Exception
     */
    protected function processApiResponse($json)
    {
      $decoded = json_decode($json, true);
  
      // check for Kiva-supplied error details
      // todo: should also check http status code returned from API call
  
      if (isset($decoded['code']) && isset($decoded['message']))
      {
        throw new Exception("API error ({$decoded['code']}): {$decoded['message']}");
      }
  
      return $decoded;
    }
  
    /**
     * Data for offline testing.
     * 
     * @param string $functionName
     * @return string
     */
    protected function getTestData($functionName)
    {
      switch ($functionName)
      {
        case 'getFundedLoans':
          // test data for funded loans (as of 2017-02-19)
          $testData = '{"paging":{"page":1,"total":1160564,"page_size":20,"pages":58029},"loans":[{"id":1237937,"name":"Sokrann\'s Group","description":{"languages":["en"]},"status":"funded","funded_amount":200,"image":{"id":2439488,"template_id":1},"activity":"Home Appliances","sector":"Personal Use","themes":["Water and Sanitation"],"use":"to buy a water filter to provide safe drinking water for their family . ","location":{"country_code":"KH","country":"Cambodia","town":"Pursat","geo":{"level":"town","pairs":"12.5 104","type":"point"}},"partner_id":311,"posted_date":"2017 - 02 - 20T07:10:05Z","planned_expiration_date":"2017 - 03 - 22T06:10:05Z","loan_amount":200,"borrower_count":5,"lender_count":8,"bonus_credit_eligibility":false,"tags":[{"name":"#Eco-friendly"},{"name":"#Health and Sanitation"},{"name":"#Technology"}]},{"id":1238740,"name":"Wilfreda","description":{"languages":["en"]},"status":"funded","funded_amount":125,"image":{"id":2442269,"template_id":1},"activity":"General Store","sector":"Retail","use":"to purchase grocery products to sell.","location":{"country_code":"PH","country":"Philippines","town":"Ozamis  City, Misamis  Occidental","geo":{"level":"town","pairs":"13 122","type":"point"}},"partner_id":136,"posted_date":"2017-02-20T06:10:02Z","planned_expiration_date":"2017-03-22T05:10:02Z","loan_amount":125,"borrower_count":1,"lender_count":2,"bonus_credit_eligibility":true,"tags":[{"name":"#Woman Owned Biz"},{"name":"#Elderly"},{"name":"#Repeat Borrower"}]},{"id":1239715,"name":"Armand","description":{"languages":["fr","en"]},"status":"funded","funded_amount":100,"image":{"id":2443628,"template_id":1},"activity":"Poultry","sector":"Agriculture","use":"to buy 40 chicks and two bags of feed.","location":{"country_code":"MG","country":"Madagascar","town":"Antsirabe","geo":{"level":"town","pairs":"-20 47","type":"point"}},"partner_id":359,"posted_date":"2017-02-20T06:00:05Z","planned_expiration_date":"2017-03-22T05:00:05Z","loan_amount":100,"borrower_count":1,"lender_count":4,"bonus_credit_eligibility":false,"tags":[{"name":"#Animals"}]},{"id":1238735,"name":"Esmeralda","description":{"languages":["en"]},"status":"funded","funded_amount":325,"image":{"id":2442262,"template_id":1},"activity":"Farming","sector":"Agriculture","use":"to buy fertilizers and other farm supplies.","location":{"country_code":"PH","country":"Philippines","town":"Canlaon, Negros Oriental","geo":{"level":"town","pairs":"13 122","type":"point"}},"partner_id":145,"posted_date":"2017-02-20T05:10:04Z","planned_expiration_date":"2017-03-22T04:10:04Z","loan_amount":325,"borrower_count":1,"lender_count":1,"bonus_credit_eligibility":true,"tags":[]},{"id":1238731,"name":"Epifania","description":{"languages":["en"]},"status":"funded","funded_amount":150,"image":{"id":2442259,"template_id":1},"activity":"Personal Housing Expenses","sector":"Housing","themes":["Green"],"use":"to build a sanitary toilet for her family.","location":{"country_code":"PH","country":"Philippines","town":"Canlaon, Negros Oriental","geo":{"level":"town","pairs":"13 122","type":"point"}},"partner_id":145,"posted_date":"2017-02-20T04:50:04Z","planned_expiration_date":"2017-03-22T03:50:04Z","loan_amount":150,"borrower_count":1,"lender_count":6,"bonus_credit_eligibility":true,"tags":[{"name":"#Eco-friendly"},{"name":"#Repeat Borrower"}]},{"id":1238727,"name":"Elvira","description":{"languages":["en"]},"status":"funded","funded_amount":150,"image":{"id":2442254,"template_id":1},"activity":"Personal Housing Expenses","sector":"Housing","themes":["Green"],"use":"to build a sanitary toilet for her family.","location":{"country_code":"PH","country":"Philippines","town":"Canlaon, Negros Oriental","geo":{"level":"town","pairs":"13 122","type":"point"}},"partner_id":145,"posted_date":"2017-02-20T04:20:03Z","planned_expiration_date":"2017-03-22T03:20:03Z","loan_amount":150,"borrower_count":1,"lender_count":6,"bonus_credit_eligibility":true,"tags":[{"name":"#Eco-friendly"}]},{"id":1238671,"name":"Felimon","description":{"languages":["en"]},"status":"funded","funded_amount":600,"image":{"id":2442169,"template_id":1},"activity":"Food","sector":"Food","use":"to buy more sacks of dried coconut meat to sell.","location":{"country_code":"PH","country":"Philippines","town":"Bindoy, Negros Oriental","geo":{"level":"town","pairs":"13 122","type":"point"}},"partner_id":145,"posted_date":"2017-02-20T04:10:04Z","planned_expiration_date":"2017-03-22T03:10:04Z","loan_amount":600,"borrower_count":1,"lender_count":3,"bonus_credit_eligibility":true,"tags":[{"name":"#Elderly"}]},{"id":1241128,"name":"Maria Leonor","description":{"languages":["es","en"]},"status":"funded","funded_amount":525,"image":{"id":2444219,"template_id":1},"activity":"Food Production\\/Sales","sector":"Food","themes":["Vulnerable Groups"],"use":"to buy ingredients to make tamales and bread, such as oil, beef, chicken, corn, sugar, eggs, flour, milk, yeast, and other supplies.","location":{"country_code":"HN","country":"Honduras","town":"Cofradia, Cortes","geo":{"level":"town","pairs":"15 -86.5","type":"point"}},"partner_id":201,"posted_date":"2017-02-20T04:00:09Z","planned_expiration_date":"2017-03-22T03:00:09Z","loan_amount":525,"borrower_count":1,"lender_count":12,"bonus_credit_eligibility":true,"tags":[{"name":"#Woman Owned Biz"},{"name":"#Parent"},{"name":"#Single Parent"}]},{"id":1238669,"name":"Bella","description":{"languages":["en"]},"status":"funded","funded_amount":325,"image":{"id":2442141,"template_id":1},"activity":"Fruits & Vegetables","sector":"Food","use":"to purchase cabbage, garlic, onions, carrots, beans, and ginger to sell.","location":{"country_code":"PH","country":"Philippines","town":"Gucab Echague, Isabela","geo":{"level":"town","pairs":"13 122","type":"point"}},"partner_id":123,"posted_date":"2017-02-20T04:00:04Z","planned_expiration_date":"2017-03-22T03:00:04Z","loan_amount":325,"borrower_count":1,"lender_count":6,"bonus_credit_eligibility":true,"tags":[{"name":"#Woman Owned Biz"},{"name":"#Widowed"}]},{"id":1241167,"name":"Fernanda","description":{"languages":["es","en"]},"status":"funded","funded_amount":200,"image":{"id":2445494,"template_id":1},"activity":"Higher education costs","sector":"Education","themes":["Higher Education"],"use":"pay university fees.","location":{"country_code":"PY","country":"Paraguay","town":"Coronel Oviedo","geo":{"level":"town","pairs":"-25.416667 -56.45","type":"point"}},"partner_id":58,"posted_date":"2017-02-20T04:00:03Z","planned_expiration_date":"2017-03-22T03:00:02Z","loan_amount":200,"borrower_count":1,"lender_count":7,"bonus_credit_eligibility":true,"tags":[{"name":"volunteer_pick"},{"name":"volunteer_like"},{"name":"user_favorite"},{"name":"#Schooling"}]},{"id":1239778,"name":"Vaonirina","description":{"languages":["fr","en"]},"status":"funded","funded_amount":150,"image":{"id":2276066,"template_id":1},"activity":"Grocery Store","sector":"Food","use":"to purchase basic necessities: oil, rice, and soap.","location":{"country_code":"MG","country":"Madagascar","town":"Antananarivo","geo":{"level":"town","pairs":"-20 47","type":"point"}},"partner_id":443,"posted_date":"2017-02-20T03:40:04Z","planned_expiration_date":"2017-03-22T02:40:04Z","loan_amount":150,"borrower_count":1,"lender_count":6,"bonus_credit_eligibility":false,"tags":[{"name":"volunteer_like"},{"name":"#Repeat Borrower"}]},{"id":1241144,"name":"Elida","description":{"languages":["es","en"]},"status":"funded","funded_amount":200,"image":{"id":2445459,"template_id":1},"activity":"Higher education costs","sector":"Education","themes":["Higher Education"],"use":"pay university fees.","location":{"country_code":"PY","country":"Paraguay","town":"Caaguaz\\u00fa","geo":{"level":"town","pairs":"-22.993333 -57.996389","type":"point"}},"partner_id":58,"posted_date":"2017-02-20T03:40:02Z","planned_expiration_date":"2017-03-22T02:40:02Z","loan_amount":200,"borrower_count":1,"lender_count":8,"bonus_credit_eligibility":true,"tags":[{"name":"volunteer_pick"},{"name":"volunteer_like"},{"name":"#Schooling"}]},{"id":1238186,"name":"Marsella","description":{"languages":["en"]},"status":"funded","funded_amount":100,"image":{"id":2441226,"template_id":1},"activity":"Dairy","sector":"Agriculture","use":"to buy a calf in order to increase milk production.","location":{"country_code":"KE","country":"Kenya","town":"Gatundu","geo":{"level":"town","pairs":"1 38","type":"point"}},"partner_id":388,"posted_date":"2017-02-20T03:30:04Z","planned_expiration_date":"2017-03-22T02:30:04Z","loan_amount":100,"borrower_count":1,"lender_count":4,"bonus_credit_eligibility":false,"tags":[{"name":"#Animals"},{"name":"#Parent"},{"name":"#Schooling"}]},{"id":1241163,"name":"Lorena","description":{"languages":["es","en"]},"status":"funded","funded_amount":475,"image":{"id":1910713,"template_id":1},"activity":"Higher education costs","sector":"Education","themes":["Higher Education"],"use":"to pay for tuition fees.","location":{"country_code":"PY","country":"Paraguay","town":"Ybycu\\u00ed","geo":{"level":"town","pairs":"-22.993333 -57.996389","type":"point"}},"partner_id":58,"posted_date":"2017-02-20T03:30:02Z","planned_expiration_date":"2017-03-22T02:30:02Z","loan_amount":475,"borrower_count":1,"lender_count":18,"bonus_credit_eligibility":true,"tags":[{"name":"#Schooling"},{"name":"#Repeat Borrower"}]},{"id":1241145,"name":"Marisol Isabel","description":{"languages":["es","en"]},"status":"funded","funded_amount":225,"image":{"id":2445460,"template_id":1},"activity":"Agriculture","sector":"Agriculture","use":"pay for manual labour for the next corn harvest.","location":{"country_code":"NI","country":"Nicaragua","town":"Boaco","geo":{"level":"town","pairs":"13 -85","type":"point"}},"partner_id":176,"posted_date":"2017-02-20T03:20:03Z","planned_expiration_date":"2017-03-22T02:20:03Z","loan_amount":225,"borrower_count":1,"lender_count":9,"bonus_credit_eligibility":true,"tags":[{"name":"volunteer_pick"},{"name":"volunteer_like"}]},{"id":1237104,"name":"Ulboshoy","description":{"languages":["ru","en"]},"status":"funded","funded_amount":150,"image":{"id":2440110,"template_id":1},"activity":"Animal Sales","sector":"Agriculture","use":"to purchase livestock to develop business.","location":{"country_code":"TJ","country":"Tajikistan","town":"Khuroson","geo":{"level":"town","pairs":"39 71","type":"point"}},"partner_id":63,"posted_date":"2017-02-20T03:20:02Z","planned_expiration_date":"2017-03-22T02:20:02Z","loan_amount":150,"borrower_count":1,"lender_count":6,"bonus_credit_eligibility":true,"tags":[{"name":"#Animals"}]},{"id":1239769,"name":"Kossiwa","description":{"languages":["fr","en"]},"status":"funded","funded_amount":150,"image":{"id":2443705,"template_id":1},"activity":"Beauty Salon","sector":"Services","use":"to buy three dozens of hair weaves and two dozens of hair extensions.","location":{"country_code":"TG","country":"Togo","town":"Vakpossito","geo":{"level":"town","pairs":"8 1.166667","type":"point"}},"partner_id":296,"posted_date":"2017-02-20T03:10:04Z","planned_expiration_date":"2017-03-22T02:10:04Z","loan_amount":150,"borrower_count":1,"lender_count":2,"bonus_credit_eligibility":false,"tags":[{"name":"volunteer_pick"},{"name":"volunteer_like"},{"name":"#Woman Owned Biz"}]},{"id":1238718,"name":"Zeynep","description":{"languages":["en"]},"status":"funded","funded_amount":425,"image":{"id":2314314,"template_id":1},"activity":"Retail","sector":"Retail","themes":["Underfunded Areas"],"use":"to buy Tupperware and LR products in bulk.","location":{"country_code":"TR","country":"Turkey","geo":{"level":"country","pairs":"39 35","type":"point"}},"partner_id":198,"posted_date":"2017-02-20T03:10:03Z","planned_expiration_date":"2017-03-22T02:10:03Z","loan_amount":425,"borrower_count":1,"lender_count":17,"bonus_credit_eligibility":false,"tags":[{"name":"#Woman Owned Biz"},{"name":"#Parent"},{"name":"#Repeat Borrower"}]},{"id":1241160,"name":"Noelia","description":{"languages":["es","en"]},"status":"funded","funded_amount":275,"image":{"id":2445477,"template_id":1},"activity":"Higher education costs","sector":"Education","themes":["Higher Education"],"use":"to pay university costs.","location":{"country_code":"PY","country":"Paraguay","town":"Ita","geo":{"level":"town","pairs":"-25.483333 -57.35","type":"point"}},"partner_id":58,"posted_date":"2017-02-20T03:10:02Z","planned_expiration_date":"2017-03-22T02:10:02Z","loan_amount":275,"borrower_count":1,"lender_count":11,"bonus_credit_eligibility":true,"tags":[{"name":"#Schooling"}]},{"id":1238716,"name":"Concepcion","description":{"languages":["en"]},"status":"funded","funded_amount":250,"image":{"id":2442230,"template_id":1},"activity":"Retail","sector":"Retail","use":"to purchase additional inventory of bamboo to sell.","location":{"country_code":"PH","country":"Philippines","town":"Puerto Princesa North, Palawan","geo":{"level":"town","pairs":"13 122","type":"point"}},"partner_id":145,"posted_date":"2017-02-20T03:00:05Z","planned_expiration_date":"2017-03-22T02:00:04Z","loan_amount":250,"borrower_count":1,"lender_count":9,"bonus_credit_eligibility":true,"tags":[{"name":"#Woman Owned Biz"},{"name":"#Elderly"}]}]}';
          break;
        
        case 'getLoanInfo':
          // test data for loan 1237937
          $testData = '{"loans":[{"id":1237937,"name":"Sokrann\'s Group","description":{"languages":["en"],"texts":{"en":"Sokrann\\u2019s group live in a rural village in Pursat province in Cambodia. Sokrann makes a living cultivating rice and she also does extra work as factory worker to support her family. In their village there is no reliable access to safe, clean drinking water. Having a water filter at home will help each of these women and man to safeguard the health of their families and save money on medical expenses and save time collecting fuel and boiling water."}},"status":"funded","funded_amount":200,"image":{"id":2439488,"template_id":1},"activity":"Home Appliances","sector":"Personal Use","themes":["Water and Sanitation"],"use":"to buy a water filter to provide safe drinking water for their family.","location":{"country_code":"KH","country":"Cambodia","town":"Pursat","geo":{"level":"town","pairs":"12.5 104","type":"point"}},"partner_id":311,"posted_date":"2017-02-20T07:10:05Z","planned_expiration_date":"2017-03-22T06:10:05Z","loan_amount":200,"lender_count":8,"bonus_credit_eligibility":false,"tags":[{"name":"#Eco-friendly"},{"name":"#Health and Sanitation"},{"name":"#Technology"}],"borrowers":[{"first_name":"Sokrann","last_name":"","gender":"F","pictured":true},{"first_name":"Se","last_name":"","gender":"F","pictured":true},{"first_name":"Chhat","last_name":"","gender":"F","pictured":true},{"first_name":"Bat","last_name":"","gender":"F","pictured":true},{"first_name":"Nhork","last_name":"","gender":"M","pictured":true}],"terms":{"disbursal_date":"2017-01-15T08:00:00Z","disbursal_currency":"KHR","disbursal_amount":780000,"repayment_term":8,"loan_amount":200,"local_payments":[],"scheduled_payments":[],"loss_liability":{"nonpayment":"lender","currency_exchange":"shared","currency_exchange_coverage_rate":0.1}},"payments":[],"funded_date":"2017-02-20T08:41:19Z","journal_totals":{"entries":0,"bulkEntries":0},"translator":{"byline":"Tim Gibson","image":1632475}}]}';          
          break;
        
        case 'getLenders':
          // test data for loan 1237937
          $testData = '{"paging":{"page":1,"total":8,"page_size":50,"pages":1},"lenders":[{"lender_id":"mark93741479","name":"Mark","image":{"id":1263795,"template_id":1},"whereabouts":"Yogi OKINAWA","country_code":"JP","uid":"mark93741479"},{"name":"Anonymous","image":{"id":726677,"template_id":1}},{"lender_id":"kate5743","name":"Kate","image":{"id":726677,"template_id":1},"whereabouts":"New Paltz NY","country_code":"US","uid":"kate5743"},{"lender_id":"gran8877","name":"G\\u00f6ran","image":{"id":726677,"template_id":1},"whereabouts":"","uid":"gran8877"},{"lender_id":"sheryl6326","name":"Sheryl","image":{"id":726677,"template_id":1},"whereabouts":"Queensland","country_code":"AU","uid":"sheryl6326"},{"lender_id":"enrico3703","name":"Enrico Barbuto","image":{"id":859073,"template_id":1},"whereabouts":"gravina di catania CT","country_code":"IT","uid":"enrico3703"},{"lender_id":"michelle3413","name":"Michelle","image":{"id":1006899,"template_id":1},"whereabouts":"","country_code":"AU","uid":"michelle3413"},{"name":"Anonymous","image":{"id":726677,"template_id":1}}]}';
          break;
        
        default:
          $testData = '';
          break;
      }
      
      return $testData;
    }
  }