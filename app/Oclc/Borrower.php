<?php
namespace App\Oclc;
use OCLC\Auth\WSKey;
use OCLC\Auth\AccessToken;
use OCLC\User;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Storage;
use Yaml;

class Borrower {
    /**
     * The invalid field.
     *
     * @var string
     */
    public $fname;
    public $lname;
    public $data = [];
    public $email;
    public $telephone_no;
    public $borrower_cat;
    public $city;
    public $address1;
    public $home_institution;
    public $address2;
    public $postal_code, $spouse_name, $province_state;
    public $expiry_date;    
 

    private $id;
    private $barcode;
    private $circInfo = [];
    private $defaultType = 'home';
    private $status;
    private $serviceUrl = '.share.worldcat.org/idaas/scim/v2';
    private $authorizationHeader;
    private $barcode_counter_init =  260000;
    private $oclc_data;
    
    private $eTag;
    private $borrowerCategory = 'McGill community borrower';
    private $homeBranch = 262754; // Maybe 262754
    private $institutionId;

    function __construct(array $request = []) {
	   // Set the variables
	    $this->data = $request;
	   
	   $this->fname = $request['fname'];
	   $this->lname = $request['lname'];
	   $this->email = $request['email'];
	   $this->borrower_cat = $request['borrower_cat'];
	   $this->telephone_no = $request['telephone_no'] ?? null;
	   $this->spouse_name = $request['spouse_name'] ?? null;
	   $this->home_institution = $this->get_home_institution($request['home_institution']) ?? null;
	   $this->city = $request['city'] ?? null;
	   $this->address1 = $request['address1'] ?? null;
	   $this->address2 = $request['address2'] ?? null;
	   $this->postal_code = $request['postal_code'] ?? null;
	   $this->province_state = $request['province_state'] ?? "Quebec";
	   
	   
       	   $oclc_config = config('oclc.connections.development');
	   
	   $this->institutionId = $oclc_config['institution_id'];
	   
	   // set the address
	   $this->addAddress($request);
	   // set the expiry date
	   $this->setExpiryDate();
    }
    public function create() {


      $url = 'https://' . $this->institutionId . $this->serviceUrl . '/Users/';
      $this->getAuth($url);

      $this->generateBarCode();
      // Send the request to create a record
      $state = $this->sendRequest($url, $this->getData());

      $status = $state['status'];
      

      // if success save data to $this->oclc_data
      if($state['status'] === 201) {
          $this->oclc_data = $state['body'];
          return TRUE;
      }else {
       	  $this->error_msg = $state['body'];
      }
      return FALSE;
    
    }

    public function getAuth($url) {
       $oclc_config = config('oclc.connections.development');
       $key = $oclc_config['api_key'];
       $secret = $oclc_config['api_secret'];
       $inst_id = $oclc_config['institution_id'];

       $services = array('SCIM');

       $user = new User($inst_id, $oclc_config['ppid'], $oclc_config['pdns']);

       $options = array('services' => $services);
       $wskey = new WSKey($key, $secret, $options);
       // provide the WSKEY
       $accessToken = $wskey->getAccessTokenWithClientCredentials($inst_id, $inst_id, $user);


       $this->setAuth($accessToken);

    }

    private function setExpiryDate() {
    	$this->expiry_date = '12/31/2099';
    }
    private function setAuth($token) {
    	$this->authorizationHeader = "Bearer ".$token->getValue();
    }

    private function sendRequest($url, $payload) {
	    $client = new Client(
            [
	           'curl' => []
	    ]);
	    $headers = array();
	    $headers['Authorization'] = $this->authorizationHeader;
	    $headers['User-Agent'] = 'McGill OCLC Client';
	    $headers['Content-Type'] = 'application/scim+json';
	    $body = ['headers' => $headers,
		    'json' => $payload,
		    'proxy' => [
		    	'http'  => getenv('PROXY_HTTP'), // Use this proxy with "http"
		        'https' => getenv('PROXY_HTTPS'), // Use this proxy with "https",
		    ]
                   ];
            try {
		  $response = $client->request('POST', $url, $body);
		  ob_start();
		   echo $response->getBody();
		  $body = ob_get_clean();
		  $status = $response->getStatusCode();
		  return array("response" => $response,
			 	 "body" => $body,
				 "status" => $status
		  );
	    } catch (RequestException $error) {
		  $status = $error->getResponse()->getStatusCode();
		  ob_start();
		   echo $error->getBody();
		  $body = ob_get_clean();
		  return array("error" => $error,
			 	 "body" => $body,
				 "status" => $status
		  );
	    }
    	
    }

    public function search() {
    
    }
    public function getBorrowerCategoryName($borrow_cat) {
	 $data = Yaml::parse(file_get_contents(base_path().'/borrowing_categories.yml'));
	 $key = array_search($borrow_cat, array_column($data['categories'], 'key'));
	 return $data['categories'][$key]['borrower_category'];
    	
    }
    public function getBorrowerCategoryLabel($borrow_cat) {
	 $data = Yaml::parse(file_get_contents(base_path().'/borrowing_categories.yml'));
	 $key = array_search($borrow_cat, array_column($data['categories'], 'key'));
	 return $data['categories'][$key]['label'];
    	
    }
    public function get_home_institution($key = null) {
      $borrowers = Yaml::parse(
		    file_get_contents(base_path().'/home_institutions.yml'));
      $keys = $borrowers['institutions'];
      if (!is_null($key)) {
        return $keys[$key];
      }
      return null;
    }

    public function getBorrowerCustomData3($borrow_cat) {
	 $data = Yaml::parse(file_get_contents(base_path().'/borrowing_categories.yml'));
	 $key = array_search($borrow_cat, array_column($data['categories'], 'key'));
	 return $data['categories'][$key]['wms_custom_data_3'];
    
    }
    public function getBorrowerCustomData2($borrow_cat){
	 $data = Yaml::parse(file_get_contents(base_path().'/borrowing_categories.yml'));
	 $key = array_search($borrow_cat, array_column($data['categories'], 'key'));
	 $is_home_inst = $data['categories'][$key]['home_institution'];
	 if ($is_home_inst) {
	 	return $this->home_institution;
	 }else {
	 	return $data['categories'][$key]['wms_custom_data_2'];
	 }
    
    }
    

    private function addAddress($request) {
	    if (isset($request['postal_code'])) {
	       $locality = isset($request['address2']) ? $request['address2'] : "";
	       $this->addresses[] = [
		"streetAddress" => $request['address1'], 
		"region" => $request['city'],
		"locality" => $locality,
		"postalCode" => $request['postal_code'],
		"type" => "",
		"primary" => false
	       ];
	    }
	     
    }

    //**** Accessors ***//
    public function getFNameAttribute() {
    	return $this->fname;
    }
    public function getRequestAttribute() {
    	return $this->request;
    }
    public function getEmailAttribute() {
    	return $this->email;
    }
    public function getTelephoneNoAttribute() {
    	return $this->telephone_no;
    }
    public function getLNameAttribute() {
    	return $this->lname;
    }

    public function generateBarcode() {

	if (Storage::disk('local')->exists('counter')){
	   $curr_val = (int)Storage::disk('local')->get('counter');
	   $curr_val++;
	}else {
	   $curr_val = $this->barcode_counter_init;
	}
	Storage::disk('local')->put('counter', $curr_val);


	// Read the counter
        // increament the last counter
        // write to the counter file
	$str_val = (string)($curr_val);
	$str_val = substr_replace( $str_val, "-", 3, 0 ); 
        return "EXT-".$str_val;

    }
    private function getAddresses() {
	    if($this->requiresAddress($this->borrower_cat)) {
		    return array(
			    0 => array (
			      'streetAddress' => $this->address1." ".$this->address2,
			      'locality' => $this->city ?? "",
			      'region' => $this->province_state ?? "",
			      'postalCode' => $this->postal_code ?? "",
			      'type' => $this->defaultType,
			      'primary' => false,
			   )
		   );
	    }
	    return null;

    }
    private function requiresAddress($borrow_cat) {
	 $data = Yaml::parse(file_get_contents(base_path().'/borrowing_categories.yml'));
	 $key = array_search($borrow_cat, array_column($data['categories'], 'key'));
	 return $data['categories'][$key]['need_address'];
    
    }
    private function getCustomData() {
	
	// Save data depending on the borrower category
	$custom_data_3 = $this->getBorrowerCustomData3($this->borrower_cat); 
	$custom_data_2 = $this->getBorrowerCustomData2($this->borrower_cat); 
	
	$data = [];
	$data["oclcKeyValuePairs"] = array();
	$data = array();
	
        $data_1 = array(
               "businessContext" => "Circulation_Info",
               "key" => "customdata1",
               "value" => ""
        );
        $data[] = $data_1;
        
        if (!empty($custom_data_2)) {
	   $data_2 = array(
		 "businessContext" => "Circulation_Info",
	         "key" => "customdata2",
		 "value" => $custom_data_2
	    );
	    $data[] = $data_2;
	}
        
        if (!empty($custom_data_3)) {
	   $data_3 = array(
		 "businessContext" => "Circulation_Info",
	         "key" => "customdata3",
		 "value" => $custom_data_3
	    );
	    $data[] = $data_3;
        }

        $data_4 = array(
               "businessContext" => "Circulation_Info",
               "key" => "customdata4",
               "value" => ""
        );
        $data[] = $data_4;
	
	return $data;
    
    }
    private function getCircInfo() {
	
        return array (
			'barcode' => $this->generatebarCode(),
			'borrowerCategory' => $this->getBorrowerCategoryName($this->borrower_cat),
			'homeBranch' => $this->homeBranch,
			'isVerified' => false,
	      	        "isCircBlocked" =>  true,
                        "isCollectionExempt" =>  false,
                        "isFineExempt" => false,
	);
    
    }

    private function getData() {
	$data = array (
	  'schemas' => array (
		 0 => 'urn:ietf:params:scim:schemas:core:2.0:User',
		 1 => 'urn:mace:oclc.org:eidm:schema:persona:correlationinfo:20180101',
		 2 => 'urn:mace:oclc.org:eidm:schema:persona:persona:20180305',
		 3 => 'urn:mace:oclc.org:eidm:schema:persona:wmscircpatroninfo:20180101',
		 4 => 'urn:mace:oclc.org:eidm:schema:persona:wsillinfo:20180101',
		 5 => 'urn:mace:oclc.org:eidm:schema:persona:additionalinfo:20180501'
	  ),
	  'name' => array (
		'familyName' => $this->lname,
		'givenName' => $this->fname,
		'middleName' => '',
		'honorificPrefix' => '',
		'honorificSuffix' => '',
	  ),
	  'addresses' => $this->getAddresses(),
	  'emails' => array (
		0 =>  array (
			'value' => $this->email,
			'type' => $this->defaultType,
			'primary' => true,
		),
	  ),
	  'urn:mace:oclc.org:eidm:schema:persona:wmscircpatroninfo:20180101' =>  array (
	    'circulationInfo' =>  $this->getCircInfo()
          ),
	  'urn:mace:oclc.org:eidm:schema:persona:additionalinfo:20180501' =>  array (
	    'oclcKeyValuePairs' =>  $this->getCustomData()
          ),
	  'urn:mace:oclc.org:eidm:schema:persona:persona:20180305' =>  array (
		  'institutionId' => $this->institutionId,
		  'oclcExpirationDate' => "2018-09-07T00:00:00Z",
	  ),
	);
	return $data;
    }

}


