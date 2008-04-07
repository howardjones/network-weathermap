<?php 

# defaults. Should be overwritten by the cacti config.
$cacti_base = '../../';
$cacti_url = '/';

include_once 'editor-config.php';

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
		
		if($count>-1) // old junk
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
	$network = get_network($int['ip'],$int['netmask'])."/".$int['netmask'];
	$interfaces[$key]['network'] = $network;
	
	$networks[$network] []= $key;
	$count++;
}
print "Assembled $count different network/netmask pairs\n";

$count=0;
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
		}
		print "\n";
	}
	
	if(sizeof($members)>2)
	{
		print "Create LAN NODE called $network and add LINKs from these NODEs to it:\n";
		foreach($members as $int)
		{
			$h = $interfaces[$int]['host'];
			print "  $int:: ".$interfaces[$int]['nicename'];
			print " on ".$hosts[$h]['description'];
			print " (".$hosts[$h]['hostname'].")\n";
		}
		print "\n";
	}
}
print "Trimmed $count networks with only one member interface\n";


///////////////////////////////////////////////////////////////

function ip_to_int($_ip)
{
	$tok=strtok($_ip, ".");
	$IPbit[]=$tok;

	while ($tok)
	{
		$tok=strtok(".");
		$IPbit[]=$tok;
	}

	$_output=0;

	for ($i=0; $i < 4; $i++)
	{
		$_output<<=8;
		$_output+=$IPbit[$i];
	}

	return ($_output);
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


?>
