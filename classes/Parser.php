<?php

class Parser {

    public $nmapArray = array();

    public function nmapParse($url) {
        $printOpen = true;
        $printClosed = true;
        $printFiltered = true;
        $xmlObject = simplexml_load_file($url);

        foreach($xmlObject as $host => $value) {
            if ((string) $host == "host") {
                $nmap["ports"] = array();
                $address = (string) $value->address["addr"];
                $this->nmapArray[$address] = array();

                if(!empty($value->ports->port)) {
                    foreach ($value->ports->port as $port) {
                        if (  ( ((string) $port->state["state"] == "filtered")	and	($printFiltered)) or
                            ( ((string) $port->state["state"] == "closed")	and	($printClosed)) or
                            ( ((string) $port->state["state"] == "open")	and	($printOpen))
                        ) {
                            $this->nmapArray[$address][] = array(
                                (string)$port["portid"] => array(
                                    "Protocol" => (string)$port["protocol"],
                                    "State" => (string)$port->state["state"],
                                    "Reason" => (string)$port->state["reason"],
                                    "Name" => (string)$port->service["name"],
                                    "Product" => (string)$port->service["product"],
                                    "Version" => (string)$port->service["version"]
                                ));
                        }
                    }
                }
            }
        }
    }

    public function nmap(){
        return $this->nmapArray;
    }

    function nessusValue($__xml_tree, $__tag_path)
    {
        $tmp_arr =& $__xml_tree;
        $tag_path = explode('/', $__tag_path);
        foreach($tag_path as $tag_name)
        {
            $res = false;
            foreach($tmp_arr as $key => $node)
            {
                if(is_int($key) && $node['name'] == $tag_name)
                {
                    $tmp_arr = $node;
                    $res = true;
                    break;
                }
            }
            if(!$res)
                return false;
        }
        return $tmp_arr;
    }

    function nessusArray($__url)
    {
        $xml_values = array();
        $contents = file_get_contents($__url);
        $parser = xml_parser_create('');
        if(!$parser)
            return false;

        xml_parser_set_option($parser, XML_OPTION_TARGET_ENCODING, 'UTF-8');
        xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
        xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
        xml_parse_into_struct($parser, trim($contents), $xml_values);
        xml_parser_free($parser);
        if (!$xml_values)
            return array();

        $xml_array = array();
        $last_tag_ar =& $xml_array;
        $parents = array();
        $last_counter_in_tag = array(1=>0);
        foreach ($xml_values as $data)
        {
            switch($data['type'])
            {
                case 'open':
                    $last_counter_in_tag[$data['level']+1] = 0;
                    $new_tag = array('name' => $data['tag']);
                    if(isset($data['attributes']))
                        $new_tag['attributes'] = $data['attributes'];
                    if(isset($data['value']) && trim($data['value']))
                        $new_tag['value'] = trim($data['value']);
                    $last_tag_ar[$last_counter_in_tag[$data['level']]] = $new_tag;
                    $parents[$data['level']] =& $last_tag_ar;
                    $last_tag_ar =& $last_tag_ar[$last_counter_in_tag[$data['level']]++];
                    break;
                case 'complete':
                    $new_tag = array('name' => $data['tag']);
                    if(isset($data['attributes']))
                        $new_tag['attributes'] = $data['attributes'];
                    if(isset($data['value']) && trim($data['value']))
                        $new_tag['value'] = trim($data['value']);

                    $last_count = count($last_tag_ar)-1;
                    $last_tag_ar[$last_counter_in_tag[$data['level']]++] = $new_tag;
                    break;
                case 'close':
                    $last_tag_ar =& $parents[$data['level']];
                    break;
                default:
                    break;
            };
        }
        return $xml_array;
    }
}