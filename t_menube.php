<?php 

set_time_limit(0);
header('Content-type: text/html; charset=UTF-8');
include_once "../include.inc.php";
include_once "simple_html_dom.php";
include_once("utilsx.inc.php");
require 'classes/menube_scrap.class.php';

main();


function main()
{
                $def_url = getParamOrDefault('url', 
                                  'http://blog.minube.com/las-10-estaciones-de-tren-mas-espectaculares/',
                                  $_GET);
		
		if ( isset($_GET['url']) )
		{
			$MYSQL_CREDENTIALS = parse_ini_file("config.ini");
			$ms = new MenubeScrapper($MYSQL_CREDENTIALS);

                        // what does the header mean? 
			$aHeader = $ms->getArticleHeader($def_url);
			
			
			$aHeader->stdout();
			
			$ms->saveToFile("D:/tmp/dom/1.htm");
		
			//$ms->output();
		}
		
		output_form($def_url);

}

function output_form($params)
{
	echo "<form name=myform method=GET >
			<input type=text name=url value='$params' size=200>
		 <input type=submit name=submit value=GetMenubeArticle>";
	echo "
		 <input type=button value='Clear' onclick='document.myform.url.value=\"\";'>
		 </form>
		 
		 ";
}

?>