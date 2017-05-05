<?php
// Enable all warning and error reports
error_reporting(E_ALL);
////////////////////////////////////
// PHENOMENAL JENKINS REPORT CLASS //
/////////////////////////////////////

class PhenomenalJenkinsReport {
	
	private $xml = NULL;
	
	/////////////////
	// CONSTRUCTOR //
	/////////////////
	
	public function __construct() {
		$xmlstr = file_get_contents("http://phenomenal-h2020.eu/jenkins/api/xml?depth=1");
		$this->xml = new SimpleXMLElement($xmlstr);
	}

	////////////////////
	// GET TOOL NAMES //
	////////////////////
	
	public function get_tool_names() {
		$jobnames = $this->xml->xpath('/hudson/job/name');
		$tools = array();
		while(list( , $job) = each($jobnames)) {
			if (preg_match('/^container-.*$/', $job)) {
	    		array_push($tools, $job->__toString());
			}
		}
		return $tools;
	}

	/////////////////////////
	// GET NUMBER OF TOOLS //
	/////////////////////////

	public function get_nb_tools() {
		return count($this->get_tool_names());
	}

	////////////////////////////////
	// IS TOOL LAST BUILD SUCCESS //
	////////////////////////////////
	
	public function is_tool_last_build_success($tool) {

		// Get number of builds
		$last_build_number_node = $this->xml->xpath("/hudson/job/name[text()='$tool']/../lastBuild/number");
		if (count($last_build_number_node) == 0)
			return NULL;
		$last_build_number = intval($last_build_number_node[0]->__toString());
		
		// Get last successful build number
		$last_successful_build_number_node = $this->xml->xpath("/hudson/job/name[text()='$tool']/../lastSuccessfulBuild/number");
		if (count($last_successful_build_number_node) == 0)
			return false;
		$last_successful_build_number = intval($last_successful_build_number_node[0]->__toString());
		
		return $last_successful_build_number == $last_build_number;
	}
	
	/////////////////////////////////////
	// GET NB TOOLS BUILT SUCCESSFULLY //
	/////////////////////////////////////
	
	public function get_nb_tools_built_successfully() {
		
		$n = 0;
		
		foreach($this->get_tool_names() as $tool)
			if ($this->is_tool_last_build_success($tool))
				++$n;
		
		return $n;
	}
	
	//////////////
	// TO ARRAY //
	//////////////
	
	public function to_array() {

		$a = array();
		
		// Set general information
		$a['nb_tools'] = $this->get_nb_tools();
		$a['nb_tools_built_successfully'] = $this->get_nb_tools_built_successfully();
		$a['percentage_of_tools_built_successfully'] = intval(100 * $this->get_nb_tools_built_successfully() / $this->get_nb_tools());

		// Set individual tool information
		$a['tools'] = array();
		foreach($this->get_tool_names() as $tool)
			array_push($a['tools'], array('name' => $tool, 'last_build_status' => $this->is_tool_last_build_success($tool) ? 'success' : 'failure'));
		
		return $a;
	}
	
	/////////////
	// TO JSON //
	/////////////
	
	public function to_json() {
		return json_encode($this->to_array());
	}
	
	/////////////////////
	// GET JSON REPORT //
	/////////////////////
	
	public static function get_json_report() {
		return (new PhenomenalJenkinsReport())->to_json();
	}
}

//////////
// MAIN //
//////////

print(PhenomenalJenkinsReport::get_json_report());
?>
