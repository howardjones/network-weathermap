<?php 

# defaults. Should be overwritten by the cacti config.
$cacti_base = '../../';
$cacti_url = '/';

$width = 4000;
$height = 3000;


require_once 'editor-config.php';

// check if the goalposts have moved
if( is_dir($cacti_base) && file_exists($cacti_base."/include/global.php") )
{
        // include the cacti-config, so we know about the database
        include_once($cacti_base."/include/global.php");
        $config['base_url'] = (isset($config['url_path'])? $config['url_path'] : $cacti_url);
        $cacti_found = TRUE;
}
elseif( is_dir($cacti_base) && file_exists($cacti_base."/include/config.php") )
{
        // include the cacti-config, so we know about the database
        include_once($cacti_base."/include/config.php");
        $config['base_url'] = (isset($config['url_path'])? $config['url_path'] : $cacti_url);
        $cacti_found = TRUE;
}
else
{
	print "You need to fix your editor-config.php\n";
	exit();
}

include_once($cacti_base."/lib/snmp.php");

if(!function_exists("cacti_snmp_get"))
{
	die("Cacti SNMP functions are not available");
}

# figure out which template has interface traffic. This might be wrong for you.
$data_template = "Interface - Traffic";
$data_template_id = db_fetch_cell("select id from data_template where name='".mysql_real_escape_string($data_template)."'");

$Interfaces_SQL = "select host.snmp_version,host.snmp_community,host.snmp_username,host.snmp_password,host.snmp_auth_protocol,host.snmp_priv_passphrase,host.snmp_priv_protocol,host.snmp_context,host.snmp_port,host.snmp_timeout,host.description, host.hostname, host.disabled, host_snmp_cache.* from host_snmp_cache,host where host_snmp_cache.host_id=host.id and (field_name='ifDescr' or field_name='ifName' or field_name='ifIP' or field_name='ifAlias') and host.disabled<>'on' and field_value<>'127.0.0.1' and field_value<>'0.0.0.0' and host.status=3 and host.snmp_version>0;";
$queryrows = db_fetch_assoc($Interfaces_SQL);

if( is_array($queryrows)  && sizeof($queryrows) > 0 )
{
	foreach ($queryrows as $line) 
	{
		$key =sprintf( "%06d-%010d",$line['host_id'],$line['snmp_index']);
		$hosts[$line['host_id']]['description'] = $line['description'];
		$hosts[$line['host_id']]['hostname'] = $line['hostname'];
		
		$hosts[$line['host_id']]['snmp_version'] = $line['snmp_version'];
		$hosts[$line['host_id']]['snmp_username'] = $line['snmp_username'];
		$hosts[$line['host_id']]['snmp_password'] = $line['snmp_password'];
		$hosts[$line['host_id']]['snmp_auth_protocol'] = $line['snmp_auth_protocol'];
		$hosts[$line['host_id']]['snmp_context'] = $line['snmp_context'];
		$hosts[$line['host_id']]['snmp_port'] = $line['snmp_port'];
		$hosts[$line['host_id']]['snmp_timeout'] = $line['snmp_timeout'];
		$hosts[$line['host_id']]['snmp_priv_protocol'] = $line['snmp_priv_protocol'];
		$hosts[$line['host_id']]['snmp_priv_passphrase'] = $line['snmp_priv_passphrase'];
		$hosts[$line['host_id']]['snmp_community'] = $line['snmp_community'];
				
		$interfaces[$key]['index'] = $line['snmp_index'];
		$interfaces[$key]['host'] = $line['host_id'];
		if($line['field_name'] == 'ifIP') $interfaces[$key]['ip'] = $line['field_value'];
		if($line['field_name'] == 'ifName') $interfaces[$key]['name'] = $line['field_value'];
		if($line['field_name'] == 'ifDescr') $interfaces[$key]['descr'] = $line['field_value'];
		if($line['field_name'] == 'ifAlias') $interfaces[$key]['alias'] = $line['field_value'];
	}
}

$count=0;
if(file_exists("mapper-cache.txt"))
{
	print "Reading Netmask cache...\n";
	$fd = fopen("mapper-cache.txt","r");
	while(!feof($fd))
	{
		$str = fgets($fd,4096);
		$str=str_replace("\r", "", $str);
		trim($str);

		list($key,$mask) = explode("\t",$str);
		if(preg_match("/^(\d+\.\d+\.\d+\.\d+)$/",$mask,$m) && $mask != '0.0.0.0') 
		{ $interfaces[$key]['netmask'] = $m[1]; $count++; } 
	}
	fclose($fd);
}
print "$count netmasks in the cache.\n";

print "Collected information on ".sizeof($interfaces)." interfaces and ".sizeof($hosts)." hosts.\n";

$cleaned=0;
foreach($interfaces as $key=>$int)
{
	if(!isset($int['ip']))
	{
		unset($interfaces[$key]);
		$cleaned++;
	}
	else
	{
		$interfaces[$key]['nicename'] = ( isset($int['name'])?$int['name']:( isset($int['descr'])?$int['descr']: (isset($int['alias'])?$int['alias']:"Interface #".$int['index']) )  );
	}
}

print "Removed $cleaned interfaces from search, which have no IP address.\n";

$count=0;

foreach($interfaces as $key=>$int)
{
	if(!isset($int['netmask']))
	{
		$oid = ".1.3.6.1.2.1.4.20.1.3.".$int['ip'];
		$hostid = $int['host'];
		
		if($count<100)
		{
			print "Fetching Netmask via SNMP for Host ".$int['host']."//".$int['ip']." from $oid\n";
			$result = cacti_snmp_get(
					$hosts[$hostid]["hostname"], 
					$hosts[$hostid]["snmp_community"], 
					$oid, 
					$hosts[$hostid]["snmp_version"], 
					$hosts[$hostid]["snmp_username"], 
					$hosts[$hostid]["snmp_password"], 
					$hosts[$hostid]["snmp_auth_protocol"], 
					$hosts[$hostid]["snmp_priv_passphrase"], 
					$hosts[$hostid]["snmp_priv_protocol"], 
					$hosts[$hostid]["snmp_context"], 
					$hosts[$hostid]["snmp_port"], 
					$hosts[$hostid]["snmp_timeout"], 
					SNMP_WEBUI);
			if($result != FALSE && preg_match("/^\d+.\d+.\d+.\d+$/",$result))
			{
				print "$result|\n";
				$interfaces[$key]['netmask'] = $result;
			}
			else
			{
				print "No useful result.\n";
				unset($interfaces[$key]);
			}
			$count++;
		}
		
	}
}

$count = 0;
print "Writing Netmask cache...\n";
$fd = fopen("mapper-cache.txt","w");
foreach($interfaces as $key=>$int)
{
	if(isset($int['netmask']))
	{
		fputs($fd,$key."\t".$int['netmask']."\n");
		$count++;
	}
}
fclose($fd);
print "Wrote $count cache entries.\n";
# SNMP netmask => .1.3.6.1.2.1.4.20.1.3.10.1.1.254
# SNMP interface index => .1.3.6.1.2.1.4.20.1.2.10.1.1.254

$count=0;
foreach($interfaces as $key=>$int)
{
	if(isset($int['netmask']))
	{
	$network = get_network($int['ip'],$int['netmask'])."/".get_cidr($int['netmask']);
	$interfaces[$key]['network'] = $network;
	
	$networks[$network] []= $key;
	$count++;
	}
	else
	{
		print $int['ip']."\n";;
	}
}
print "Assembled $count different network/netmask pairs\n";


$link_config = "";
$node_config = "";
$nodes_seen = array();

$count=0;
$linkid = 0;
$lannodeid = 0;
foreach ($networks as $network=>$members)
{
	if(sizeof($members)<2)
	{
		unset($networks[$network]);
		$count++;
	}
	
	if(sizeof($members)==2)
	{
		print "Create LINK between\n";
		foreach($members as $int)
		{
			$h = $interfaces[$int]['host'];
			print "  ".$interfaces[$int]['nicename'];
			print " on ".$hosts[$h]['description'];
			print " (".$hosts[$h]['hostname'].")\n";
			$nodes_seen[$h]=1;
		}
		$linkid++;
		$link_config .= "LINK link_$linkid\nWIDTH 4\n";
		$link_config .= "\tNODES node_".$interfaces[$members[0]]['host']." node_".$interfaces[$members[1]]['host']."\n";
		$link_config .= "\tSET in_interface ".$interfaces[$members[1]]['nicename']."\n";
		$link_config .= "\tSET out_interface ".$interfaces[$members[0]]['nicename']."\n";
		$link_config .=  "\n";
	}
	
	if(sizeof($members)>2)
	{
		print "Create LAN NODE called $network and add LINKs from these NODEs to it:\n";
		$x = rand(0,$width); $y = rand(0,$height);	
		$lan_key = preg_replace("/[.\/]/","_",$network);
		$node_config .= "NODE LAN_$lan_key\nLABELBGCOLOR 255 240 240 \n\tPOSITION $x $y\n\tLABEL $network\n\tICON 96 24 rbox\n\tLABELOFFSET C\n\tLABELOUTLINECOLOR none\nUSESCALE none in\n\n";
		foreach($members as $int)
		{
			$h = $interfaces[$int]['host'];
			print "  $int:: ".$interfaces[$int]['nicename'];
			print " on ".$hosts[$h]['description'];
			print " (".$hosts[$h]['hostname'].")\n";
			$nodes_seen[$h]=1;
			$linkid++;
			$link_config .= "LINK link_$linkid\n";
			$link_config .= "SET out_interface ".$interfaces[$int]['nicename']."\n";
			$link_config .= "\tNODES node_$h LAN_$lan_key\n\tWIDTH 2\n\tOUTCOMMENT {link:this:out_interface}\n";
		}
		print "\n";
	}
}
print "Trimmed $count networks with only one member interface\n";

foreach ($nodes_seen as $h=>$c)
{
	$x = rand(0,$width); $y = rand(0,$height);	
	$node_config .= "NODE node_$h\n\tSET cacti_id $h\n";
	$node_config .= "\tLABEL ".$hosts[$h]['description']."\n";
	$node_config .= "\tPOSITION $x $y\n";
	$node_config .= "\tUSESCALE cactiupdown in \n";
	$node_config .= "\tLABELFONTCOLOR contrast\n";
	$node_config .= "\n\n";
}

$fd = fopen("automap.cfg","w");
fputs($fd,"HTMLSTYLE overlib\nBGCOLOR 92 92 92\nWIDTH $width\nHEIGHT $height\nFONTDEFINE 30 GillSans 8\n");
fputs($fd,"FONTDEFINE 20 GillSans 10\nFONTDEFINE 10 GillSans 9\n");
fputs($fd,"SCALE DEFAULT 0 0 255 0 0\nSCALE DEFAULT 0 10   32 32 32   0 0 255\nSCALE DEFAULT 10 40   0 0 255   0 255 0\nSCALE DEFAULT 40 55   0 255 0   255 255 0\nSCALE DEFAULT 55 100   240 240 0   255 0 0\n");
fputs($fd,"\nSCALE cactiupdown 0 0.5 192 192 192 \nSCALE cactiupdown 0.5 1.5 255 0 0 \nSCALE cactiupdown 1.5 2.5 0 0 255 \nSCALE cactiupdown 2.5 3.5 0 255 0 \nSCALE cactiupdown 3.5 4.5 255 255 0 \n");
fputs($fd,"\nLINK DEFAULT\nBWSTYLE angled\nBWLABEL bits\nBWFONT 30\nCOMMENTFONT 30\n\n");
fputs($fd,"\nNODE DEFAULT\nLABELFONT 10\n\n");
fputs($fd,$node_config);
fputs($fd,$link_config);
fclose($fd);

///////////////////////////////////////////////////////////////

function ip_to_int($_ip)
{
	if(preg_match("/^(\d+)\.(\d+)\.(\d+)\.(\d+)$/",$_ip,$matches))
	{
		$_output=0;
		for($i=1;$i<5;$i++)
		{
			$_output<<=8;
			$_output+=$matches[$i];
		}
		return($_output);
	}
	else
	{
		print "Something funny: $_ip\n";
		return(-1);
	}
}

function int_to_ip($_int)
{
	$tmp=$_int;

	for ($i=0; $i < 4; $i++)
	{
		$IPBit[]=($tmp & 255);
		$tmp>>=8;
	}

	$_output=sprintf("%d.%d.%d.%d", $IPBit[3], $IPBit[2], $IPBit[1], $IPBit[0]);
	return ($_output);
}

function get_network($_ip, $_mask)
{
	$_int1=ip_to_int($_ip);
	$_mask1=ip_to_int($_mask);

	$_network=$_int1 & ($_mask1);

	return (int_to_ip($_network));
}

function get_cidr($mask)
{
	$lookup = array(
		"255.255.255.255"=>"32",
		"255.255.255.254"=>"31",
		"255.255.255.252"=>"30",
		"255.255.255.248"=>"29",
		"255.255.255.240"=>"28",
		"255.255.255.224"=>"27",
		"255.255.255.192"=>"26",
		"255.255.255.128"=>"25",
		"255.255.255.0"=>"24",
		"255.255.254.0"=>"23",
		"255.255.252.0"=>"22",
		"255.255.248.0"=>"21",
		"255.255.240.0"=>"20",
		"255.255.224.0"=>"19",
		"255.255.192.0"=>"18",
		"255.255.128.0"=>"17",
		"255.255.0.0"=>"16",
		"255.254.0.0"=>"15",
		"255.252.0.0"=>"14",
		"0.0.0.0.0"=>"0"
	);

	if($lookup[$mask]) return ($lookup[$mask]);

	print "HUH: $mask\n";

	return("-1");


}


?>
