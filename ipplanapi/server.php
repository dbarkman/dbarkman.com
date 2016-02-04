<?php

// IPplan v2.92
// Aug 24, 2001
//
// This program is free software; you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation; either version 2 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
//

require_once("xmlrpc/xmlrpc.inc");
require_once("xmlrpc/xmlrpcs.inc");
require_once("../ipplan/ipplanlib.php");
require_once("../ipplan/adodb/adodb.inc.php");
require_once("../ipplan/class.dbflib.php");
require_once("../ipplan/auth.php");

// API version number
define("IPPLAN_API_VER", "1");

if (!ANONYMOUS) {
   $auth = new SQLAuthenticator(REALM, REALMERROR);

   // And now perform the authentication
   $grps=$auth->authenticate();
}


// RPC-call: takes one string parameter containing a valid IP address
// Returns: all networks that contain the IP address for all customers
// Return value: array of hashes - hash has following indexes:
//			baseaddr - network containing IP address
//			descrip - description of network
//			custdescrip - description of customer to which network
//					belongs
function __SearchIP($params) {
   global $xmlrpcerruser; // import user errcode value

   // $params is an Array of xmlrpcval objects
   $errstr="";
   $err=0;

   if (IPPLAN_API_VER != DBF_API_VER)
      return new xmlrpcresp(0, $xmlrpcerruser+3, "Incorrect API version");

   // get the first param
   $ipobj=$params->getParam(0);

   // if it's there and the correct type
   if (isset($ipobj) && ($ipobj->scalartyp()=="string")) {
      // extract the value of the state number
      $ipaddr=$ipobj->scalarval();
      if (testIP($ipaddr)) {
         $err=50;
         $errstr="Invalid IP address!";
      }
      else {
         if (!$ds=new IPplanDbf()) {
            return new xmlrpcresp(0, $xmlrpcerruser+1, 
                                     "Could not connect to database");
         }

         $result=$ds->GetDuplicateSubnetAll(inet_aton($ipaddr), 1);
         // returns the following fields:
         // base.baseaddr, base.subnetsize, base.baseindex, base.descrip, customer.custdescrip, 
         // customer.customer, base.lastmod, base.userid, base.swipmod
         while ($row=$result->FetchRow()) {
            $myVal[]=new xmlrpcval(array("baseaddr" => new xmlrpcval(inet_ntoa($row["baseaddr"])),
                              "subnetsize"  => new xmlrpcval($row["subnetsize"], "int"),
                              "baseindex"  => new xmlrpcval($row["baseindex"], "int"),
                              "descrip"  => new xmlrpcval($row["descrip"]),
                              "customer"  => new xmlrpcval($row["customer"], "int"),
                              "custdescrip" => new xmlrpcval($row["custdescrip"])), "struct");
         }

      }
   } else {
      // parameter mismatch, complain
      $err=2;
      $errstr="Incorrect parameters";
   }

   if ($err) {
     // this is an error condition
      return new xmlrpcresp(0, $xmlrpcerruser+1, $errstr);
   } else {
     // this is a successful value being returned
     return new xmlrpcresp(new xmlrpcval($myVal, "array"));
   }
}


// RPC-call: takes one integer parameter containing a customer index
// Returns: all information for the requested customer
// Return value: hash - hash has following indexes:
//			org, street, city, state, zipcode, cntry, nichandl
//			lname, fname, mname, torg, tstreet, tcity, tstate
//			tzipcode, tcntry
//			dns - this is an array of hashes with the dns servers
//				hname - server name
//				ipaddr - dns server ipaddr
function __FetchCustomerInfo($params) {
   global $xmlrpcerruser; // import user errcode value

   // $params is an Array of xmlrpcval objects
   $errstr="";
   $err=0;

   if (IPPLAN_API_VER != DBF_API_VER)
      return new xmlrpcresp(0, $xmlrpcerruser+3, "Incorrect API version");

   // get the first param
   $custobj=$params->getParam(0);

   // if it's there and the correct type
   if (isset($custobj) && ($custobj->scalartyp()=="int")) {
      // extract the value of the state number
      $cust=$custobj->scalarval();

      if (!$ds=new IPplanDbf()) {
         return new xmlrpcresp(0, $xmlrpcerruser+1, 
                                  "Could not connect to database");
      }


      $result=$ds->GetCustomerDNSInfo($cust);
      while($row = $result->FetchRow()) {
         $myDNS[]=new xmlrpcval(array("hname" => new xmlrpcval($row["hname"]),
                           "ipaddr" => new xmlrpcval($row["ipaddr"])), "struct");
      }
 

      $result=$ds->GetCustomerInfo($cust);
      // only one row here
      $row=$result->FetchRow();
      $myVal=new xmlrpcval(array("org" => new xmlrpcval($row["org"]),
                           "street"  => new xmlrpcval($row["street"]),
                           "city"  => new xmlrpcval($row["city"]),
                           "state"  => new xmlrpcval($row["state"]),
                           "zipcode"  => new xmlrpcval($row["zipcode"]),
                           "cntry"  => new xmlrpcval($row["cntry"]),
                           "nichandl"  => new xmlrpcval($row["nichandl"]),
                           "lname"  => new xmlrpcval($row["lname"]),
                           "fname"  => new xmlrpcval($row["fname"]),
                           "mname"  => new xmlrpcval($row["mname"]),
                           "torg"  => new xmlrpcval($row["torg"]),
                           "tstreet"  => new xmlrpcval($row["tstreet"]),
                           "tcity"  => new xmlrpcval($row["tcity"]),
                           "tstate"  => new xmlrpcval($row["tstate"]),
                           "tzipcode"  => new xmlrpcval($row["tzipcode"]),
                           "tcntry"  => new xmlrpcval($row["tcntry"]),
                           "phne"  => new xmlrpcval($row["phne"]),
                           "mbox" => new xmlrpcval($row["mbox"]),
                           "dns" => new xmlrpcval($myDNS, "array")), "struct");

   } else {
      // parameter mismatch, complain
      $err=2;
      $errstr="Incorrect parameters";
   }

   if ($err) {
     // this is an error condition
      return new xmlrpcresp(0, $xmlrpcerruser+1, $errstr);
   } else {
     // this is a successful value being returned
     return new xmlrpcresp($myVal);
   }
}


// RPC-call: takes no parameters
// Returns: all customers valid for authenticated user, or all if no
//		authentication
// Return value: array of hashes - hash has following indexes:
//			customer - index to customers data - used with 
//				FetchBase API call
//			custdescrip - description of customer
function __FetchCustomer() {
   global $xmlrpcerruser; // import user errcode value
   global $grps;

   // $params is an Array of xmlrpcval objects
   $errstr="";
   $err=0;

   if (IPPLAN_API_VER != DBF_API_VER)
      return new xmlrpcresp(0, $xmlrpcerruser+3, "Incorrect API version");

   if (!$ds=new IPplanDbf()) {
      return new xmlrpcresp(0, $xmlrpcerruser+1, 
                               "Could not connect to database");
   }

   // nothing to display, just exit
   if (!$result=$ds->GetCustomerGrp(0)) {
      return new xmlrpcresp(0, $xmlrpcerruser+1, 
                               "No customers");
   }

   // do this here else will do extra queries for every customer
   $adminuser=$ds->TestGrpsAdmin($grps);

   while($row=$result->FetchRow()) {
      // ugly kludge with global variable!
      // remove all from list if global searching is not available
      if (!$displayall and strtolower($row["custdescrip"])=="all")
         continue;

      // strip out customers user may not see due to not being member
      // of customers admin group. $grps array could be empty if anonymous
      // access is allowed!
      if(!$adminuser) {
         if(!empty($grps)) {
            if(!in_array($row["admingrp"], $grps))
               continue;
         }
      }
      $myVal[]=new xmlrpcval(array("customer" => new xmlrpcval($row["customer"], "int"),
                        "custdescrip" => new xmlrpcval($row["custdescrip"])), "struct");
 
   }

   if ($err) {
     // this is an error condition
      return new xmlrpcresp(0, $xmlrpcerruser+1, $errstr);
   } else {
     // this is a successful value being returned
     return new xmlrpcresp(new xmlrpcval($myVal, "array"));
   }

}

// RPC-call: takes five parameters
//		1 - integer representing customer index obtained with
//			FetchCustomer API call
//		2 - integer representing areaindex
//		3 - integer representing rangeindex
//		4 - string representing ip address or network to search
//		5 - integer signifying searching for networks or ip addresses
//			0 searches for networks, 1 searches for ip addresses
//		6 - string representing filter for descriptions - this is a
//			regular expression for databases that support it
//			MySQL or Postgress
// Returns: all networks that meet search criteria
// Return value: array of hashes - hash has following indexes:
//			baseaddr - network containing IP address
//			subnetsize - size of network
//			descrip - description of network
//			lastmod - last modified date and time
function __FetchBase($params) {
   global $xmlrpcerruser; // import user errcode value
   global $grps;

   // $params is an Array of xmlrpcval objects
   $errstr="";
   $err=0;

   if (IPPLAN_API_VER != DBF_API_VER)
      return new xmlrpcresp(0, $xmlrpcerruser+3, "Incorrect API version");

   // get the first param
   $custobj=$params->getParam(0);
   $areaindexobj=$params->getParam(1);
   $rangeindexobj=$params->getParam(2);
   $ipaddrobj=$params->getParam(3);
   $searchinobj=$params->getParam(4);
   $descripobj=$params->getParam(5);
 
   // if it's there and the correct type
   if (isset($custobj) && ($custobj->scalartyp()=="int") &&
       isset($areaindexobj) && ($areaindexobj->scalartyp()=="int") &&
       isset($rangeindexobj) && ($rangeindexobj->scalartyp()=="int") &&
       isset($ipaddrobj) && ($ipaddrobj->scalartyp()=="string") &&
       isset($searchinobj) && ($searchinobj->scalartyp()=="int") &&
       isset($descripobj) && ($descripobj->scalartyp()=="string")) {

      if (!$ds=new Base()) {
         return new xmlrpcresp(0, $xmlrpcerruser+1, 
                                  "Could not connect to database");
      }

      $ds->SetGrps($grps);
      $ds->SetIPaddr($ipaddrobj->scalarval());
      $ds->SetSearchIn($searchinobj->scalarval());
      $ds->SetDescrip($descripobj->scalarval());

      $result = $ds->FetchBase($custobj->scalarval(), 
                               $areaindexobj->scalarval(), 
                               $rangeindexobj->scalarval());
      if (!$result) {
         return new xmlrpcresp(0, $xmlrpcerruser+$ds->err, 
                                  $ds->errstr);
      }

      while ($row=$result->FetchRow()) {
         $myVal[]=new xmlrpcval(array("baseaddr" => new xmlrpcval(inet_ntoa($row["baseaddr"])),
                           "baseindex"  => new xmlrpcval($row["baseindex"], "int"),
                           "subnetsize"  => new xmlrpcval($row["subnetsize"], "int"),
                           "descrip"  => new xmlrpcval($row["descrip"]),
                           "lastmod" => new xmlrpcval($result->UserTimeStamp($row["lastmod"], "M d Y H:i:s"))), "struct");
      }

   } else {
      // parameter mismatch, complain
      $err=2;
      $errstr="Incorrect parameters";
   }

   if ($err) {
     // this is an error condition
      return new xmlrpcresp(0, $xmlrpcerruser+1, $errstr);
   } else {
     // this is a successful value being returned
     return new xmlrpcresp(new xmlrpcval($myVal, "array"));
   }
}

// RPC-call: takes one parameter
//		1 - integer representing network index obtained with
//			FetchBase
// Returns: all ip addresses in network that meet search criteria
// Return value: array of hashes - hash has following indexes:
//			userinf - user information
//			location - user location
//			telno - user telefone number
//			descrip - description of device
//			lastmod - last modified date and time
function __FetchSubnet($params) {
   global $xmlrpcerruser; // import user errcode value

   // $params is an Array of xmlrpcval objects
   $errstr="";
   $err=0;

   if (IPPLAN_API_VER != DBF_API_VER)
      return new xmlrpcresp(0, $xmlrpcerruser+3, "Incorrect API version");

   // get the first param
   $baseindexobj=$params->getParam(0);

   // if it's there and the correct type
   if (isset($baseindexobj) && ($baseindexobj->scalartyp()=="int")) {
      // extract the value of the state number
      $baseindex=$baseindexobj->scalarval();
      if (!$ds=new IPplanDbf()) {
         return new xmlrpcresp(0, $xmlrpcerruser+1, 
                                  "Could not connect to database");
      }

      // get info from base table
      $result=$ds->GetSubnetDetails($baseindex);

      while ($row=$result->FetchRow()) {
         $myVal[]=new xmlrpcval(array("ipaddr" => new xmlrpcval(inet_ntoa($row["ipaddr"])),
                           "userinf"  => new xmlrpcval($row["userinf"]),
                           "location"  => new xmlrpcval($row["location"]),
                           "descrip"  => new xmlrpcval($row["descrip"]),
                           "telno"  => new xmlrpcval($row["telno"]),
                           "lastmod" => new xmlrpcval($result->UserTimeStamp($row["lastmod"], "M d Y H:i:s"))), "struct");
      }

   } else {
      // parameter mismatch, complain
      $err=2;
      $errstr="Incorrect parameters";
   }

   if ($err) {
     // this is an error condition
      return new xmlrpcresp(0, $xmlrpcerruser+1, $errstr);
   } else {
     // this is a successful value being returned
     return new xmlrpcresp(new xmlrpcval($myVal, "array"));
   }
}


// start of actual server process
$s=new xmlrpc_server( array("ipplan.SearchIP" => array("function" => "__SearchIP"),
                            "ipplan.FetchCustomerInfo" => array("function" => "__FetchCustomerInfo"),
                            "ipplan.FetchCustomer" => array("function" => "__FetchCustomer"),
                            "ipplan.FetchSubnet" => array("function" => "__FetchSubnet"),
                            "ipplan.FetchBase" => array("function" => "__FetchBase")));

