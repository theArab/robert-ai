<?php 
$method = $_SERVER['REQUEST_METHOD'];
// weather underground_key:  aca2d64fd262a6be
// Process only when method is POST
if($method == 'POST'){
	$requestBody = file_get_contents('php://input');
	$json = json_decode($requestBody);
	$search_term=$json->result->parameters->search_term;
	$search_img=$json->result->parameters->search_img;
	$wiki=$json->result->parameters->wiki;
	$say_this=$json->result->parameters->say_this;
	$text_msg=$json->result->parameters->text_msg;
	$define_word=$json->result->parameters->define_word;
	$define_slang=$json->result->parameters->define_slang;
	$yoda=$json->result->parameters->yoda;

function safe($str){
	$str=ucwords($str); // capitalize the words 
	$safe=str_replace(' ', '%20', $str);
	return $safe;
}

function parse($url){
	$json_string=file_get_contents($url);
	$parsed_json=json_decode($json_string);
	return $parsed_json;
}

	//-- IMAGE
	if($search_img!=""){
//-- was trying to get the url that im on to show actual image in FB, but didnt work		
//		$this_url=(isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
//		$parse_url= parse_url($this_url);
//		$domain=$parse_url['host'];
//		$result.=$domain;
		$title=$search_img;
		$safe=safe($search_img);
		$json=parse('http://en.wikipedia.org/w/api.php?action=query&format=json&prop=pageimages&piprop=original&titles='.$safe.'&');
		foreach($json->query->pages as $k){
			$img_url=$k->original->source;
//			$real_img.='<meta property="og:image" content="'.$img_url.'" />';
			$result.=$real_img.$img_url;
		}
		$speech=$result;
	//-- YODA
	}else if($yoda!=""){
		$safe=safe($yoda);
		$json=parse('http://api.funtranslations.com/translate/yoda.json?text='.$safe.'&');
		foreach($json as $q){$trans=$q->translated;}
		$speech=$trans;
	//-- SLANG DEFINITION
	}else if($define_slang!=""){
		$title=$define_slang;
		$safe=safe($define_slang);
		$json=parse('http://api.urbandictionary.com/v0/define?term='.$safe.'&');
		$total=count($json);
		$num_def=0;
		foreach($json as $q){
			$def=$q[$num_def]->definition;
			$example=$q[$num_def]->example;
			if($def!="" && $example!=""){
				$result.="Definition: ".$def.". Example: ".$example;
			}
			$num_def++;
		}
		$speech=$result;

	//-- DEFINE
	}else if($define_word!=""){
		$title=$define_word;
		$safe=safe($define_word);
		$json=parse('https://owlbot.info/api/v2/dictionary/'.$safe.'&');
		$total=count($json);
		$num_entries=1;
		if($total>0){
			if($total==1){$result="I found 1 definition for the word: ".$define_word.". ";}else{$result="I found ".$total." definitions for ".$define_word.". ";}
				for($x=0; $x<$total; $x++){
				$this_ar=$json[$x];
				$type=$this_ar->type;
				$def=$this_ar->definition;
				$this_result="Definition ".$num_entries.". ".$type.". ".$def." ";
				$result.=$this_result;
				$num_entries++;
			}
		}else{
			$result="I could not find a definition for ".$define_word.". ";
		}
		$speech=$result;
	//-- TEXT MSG
	}else if($text_msg!=""){
		$saveIt=mail("4073859518@vtext.com", "", $text_msg, null, "-frobert@fonebug.com");
		if(!$saveIt){
			$speech="Could not send text message.";
		}else{
			$speech="Sent to: ".$text_msg;
		}

	//-- SAY THIS
	}else if($say_this!=""){
		$speech=$say_this;

	//-- SEARCH
	}else if($wiki!=""){
		$safe=safe($wiki);
		$title=$wiki;
		$json=parse('https://en.wikipedia.org/w/api.php?action=query&prop=extracts&format=json&exintro=&titles='.$safe.'&.');
//		$json=parse('https://en.wikipedia.org/w/api.php?format=json&action=query&titles='.$safe.'&prop=revisions&rvprop=content');
		foreach($json->query->pages as $k){$result.=$k->extract;}
		if($result!=""){$result.="... this ends Robert's research of ".$wiki;}
		$speech=strip_tags($result);
	}else{
		$speech = "Sorry, I am not sure what you want to do.";
	}

	//-- RESPONSE --//
	if($speech==""){
		$error_msg_extra=' Maybe try: "search '.$title.'" or: "show '.$title.'" or: "define '.$title.'" or: "slang '.$title.'"';
		$speech="I could not find anything for: ".$title.".".$error_msg_extra;
	}
	$response=new \stdClass();
	$response->speech=$speech;
	$response->displayText=$speech;
	$response->source="webhook";
	$response->displayImage=$final_img;
	echo json_encode($response);
}else{
	echo "Method not allowed";
}
?>