<?php

class ClusterList {
	
	private static $clusters;
	
	public static function getClusters() {
		$clusters = array();
		//get clusters from yaml file
		$input = file_get_contents("connections/clusters.yml");
		
		
		if ($input !== "") {
			$parsed = yaml_parse($input);
			
			foreach ($parsed['clusters'] as $c) {				
				array_push($clusters, Cluster::fromArray($c));
			}

			return $clusters;		
		}
				
		return null;
	}
	
	public static function getCluster($name) {
		$clusters = ClusterList::getClusters();
		foreach ($clusters as $c) {
			if ($c->name == $name) {
				return $c;
			}			
		}		
		return null;
	}
	
	public static function addCluster($cluster) {
		
		$input = file_get_contents("connections/clusters.yml");
		$parsed = array();
		
		if ($input !== "") {
			$parsed = yaml_parse($input);
			//var_dump($parsed);
			array_push($parsed['clusters'], $cluster->toArray());
		} else {
			$parsed['clusters'] = array();
			$parsed['clusters'][0] = $cluster->toArray();
		}
						
		yaml_emit_file("connections/clusters.yml", $parsed);
	}
	
	
}
