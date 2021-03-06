<?php
//   Copyright 2016 John Collins

// *****************************************************************************
// PLEASE BE CAREFUL ABOUT EDITING THIS FILE, IT IS SOURCE-CONTROLLED BY GIT!!!!
// Your changes may be lost or break things if you don't do it correctly!
// *****************************************************************************

//   This program is free software: you can redistribute it and/or modify
//   it under the terms of the GNU General Public License as published by
//   the Free Software Foundation, either version 3 of the License, or
//   (at your option) any later version.

//   This program is distributed in the hope that it will be useful,
//   but WITHOUT ANY WARRANTY; without even the implied warranty of
//   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//   GNU General Public License for more details.

//   You should have received a copy of the GNU General Public License
//   along with this program.  If not, see <http://www.gnu.org/licenses/>.

include 'php/tcerror.php';
include 'php/session.php';
include 'php/tdate.php';
include 'php/club.php';
include 'php/country.php';
include 'php/rank.php';
include 'php/person.php';
include 'php/entrant.php';
include 'php/tournclass.php';
include 'php/opendb.php';
include 'php/player.php';

if (!isset($_GET['tcode']))  {
	$mess = "No code";
	include 'php/wrongentry.php';
	exit(0);
}

$tcode = $_GET['tcode'];

try  {
	opendb();
	$tourn = new Tournament($tcode);
	$tourn->fetchdets();
	//$players = Player::list_players();
	$clubs = Club::list_clubs();
	$countries = Country::list_countries();
}
catch (Tcerror $e)  {
	$mess = $e->getMessage();
	include 'php/wrongentry.php';
	exit(0);
}

// If this is my tourbament, then we allow things like editing other users

$mytournament = $admin || ($organ && $tourn->Orguser == $userid);

// Get my userid and player details

$defplayer = new Player();

try  {
	// If we are allowed to and a player is given, pick up the details for that player,
	// otherwise grab from the userid
	
	if ($mytournament && isset($_GET['f']) && isset($_GET['l']))  {
		$defplayer->fromget();
		$defplayer->fetchplayer();
	}
	elseif (strlen($userid) != 0)  {
		$defplayer->fromid($userid);
	}
}
catch (Tcerror $e) {
	$mess = "Cannot get details for {$defplayer->First} {$defplayer->Last} or useid $userid";
	include 'php/wrongentry.php';
	exit(0);
}

// Fetch details of any previous entry with same name
// Otherwise make one up with defaults from person if any.

$preventry = new Entrant();
if  ($defplayer->isdefined())  {
	$preventry->cloneperson($defplayer);
	try  {
		$preventry->fetchdets($tourn);
		$defplayer->Club = $preventry->Club;
		$defplayer->Rank = $preventry->Rank;
		$defplayer->Country = $preventry->Country;
		$defplayer->Nonbga = $preventry->Nonbga;
		$defplayer->Email = $preventry->Email;
	}
	catch (Tcerror $e)  {
		$preventry->Club = $defplayer->Club;
		$preventry->Rank = $defplayer->Rank;
		$preventry->Country = $defplayer->Country;
		$preventry->Nonbga = $defplayer->Nonbga;
		$preventry->Email = $defplayer->Email;
	}
}

// OK kick off the form

$Title = "Entry for tournament {$tourn->display_name()}";
include 'php/head.php';

// There follows a pleasing mixture of lumps of PHP and JavaScript.
// "Enjoy"

print <<<EOT
<body onload="calccost();">
<script language="javascript" src="webfn.js"></script>
<script language="javascript">
function checkform() {
	var fm = document.entryform;
   if  (!nonblank(fm.name.value))  {
   	alert("Please give your name");
      return false;
   }
   if (!okname(fm.name.value))  {
   	alert("Sorry unacceptable name format expecting first-name(space) last-name");
      return false;
   }
   if (!nonblank(fm.club.value))  {
		alert("No club given");
      return  false;
   }
   if (!nonblank(fm.country.value))  {
     	alert("No country given");
      return  false;
   }
   if (!okclubcountry(fm.club.value))  {
   	alert("Invalid club name, must be alphabetic, spaces, hyphen");
   	return  false;
   }
   if (!okclubcountry(fm.country.value))  {
   	alert("Invalid country name, must be alphabetic, spaces, hyphen");
   	return  false;
   }
   if (!nonblank(fm.email.value))  {
	   alert("Please give your email address");
		return  false;
	}
	if (!checkchallenge(fm.asp.value))  {
		alert("Invalid challenge response, should be word");
		return  false;
	}

EOT;
if ($tourn->Nonbga != 0)
	print <<<EOT
   var nbgasel = fm.nonbga;
	var optl = nbgasel.options;
	if (optl[nbgasel.selectedIndex].value == 'u')  {
		alert("Please select BGA membership");
	   return  false;
	}

EOT;
	 print <<<EOT
    return true;
}

function calccost() {
	var fm = document.entryform;
	var cost = {$tourn->display_basic_fee()};

EOT;

// Only put in code for non-bga if we have a charge for it
// (provides for candidates where we don't allow such folk in)

if ($tourn->Nonbga != 0)
	print <<<EOT
	if (fm.nonbga.options[fm.nonbga.selectedIndex].value == 'n')
		cost += {$tourn->display_nonbga()};

EOT;

	// Process concession radio boxes but only if we have one.
	// If there are any concessions there will be a radio button set

if  ($tourn->Concess1 != 0  ||  $tourn->Concess2 != 0)  {		// Don't bother if none
	print <<<EOT
	var radios = document.getElementsByName("concess");
	var i;
	var chkd = "std";
	for (i in radios)
		if (radios[i].checked)
			chkd = radios[i].value;

EOT;

	// If we have concession 1 insert code to check that radio button got set
		
	if  ($tourn->Concess1 != 0)
		print <<<EOT
	if (chkd == "C1")
		cost -= {$tourn->display_concess1()};

EOT;

	// Ditto concession 2

	if  ($tourn->Concess2 != 0)
		print <<<EOT
	if (chkd == "C2")
		cost -= {$tourn->display_concess2()};

EOT;
}

//  If there is a lunch insert code to calculate it
		
if  ($tourn->Lunch > 0)
	print <<<EOT
	if (fm.lunch.checked)
		cost += {$tourn->display_lunch()};

EOT;

print <<<EOT
	costp = Math.floor(cost);
	costd = Math.floor((cost - costp) * 100) + 100;
	costd += '';
	fm.cost.value = costp + '.' + costd.substr(1);
}

function club_sel() {
	var fm = document.entryform;
	var ps = fm.clubsel;
	var cs = fm.countrysel;
	var psi = ps.selectedIndex;
	if  (psi <= 0)
		return;
	var optv = ps.options[psi].value;
	var parts = optv.split(':');
	fm.club.value = parts[0];
	var cntry = parts[1];
	var n;
	for (n = 1;  n < cs.options.length; n++) {
		if (cs.options[n].value == cntry)  {
			cs.selectedIndex = n;
			break;
		}
	}
	fm.country.value = cntry;
}

function country_sel() {
	var fm = document.entryform;
	var cs = fm.countrysel;
	var csi = cs.selectedIndex;
	if  (csi <= 0)
		return;
	fm.country.value = cs.options[csi].value;
}

function clubedited()  {
	var fm = document.entryform;
	fm.clubsel.selectedIndex = -1;
}
function countryedited()  {
	var fm = document.entryform;
	fm.countrysel.selectedIndex = -1;
}
</script>

EOT;

include 'php/nav.php';

print <<<EOT
<h2>Entry form for: {$tourn->display_name()}</h2>
<p>{$tourn->html_format()}</p>
<p>{$tourn->html_over()}</p>

EOT;

if ($tourn->Provisional)
	print <<<EOT
<p><b>Please note that this tournament is <u>provisional</u>. dates, arrangements and fees may be subject to change.</b></p>

EOT;

print <<<EOT
<form name="entryform" action="eform2.php" method="post" enctype="application/x-www-form-urlencoded" onsubmit="javascript:return checkform();">
<input type="hidden" name="tcode" value="$tcode" />
<table>

<tr>
	<td>Player Name</td>
	<td><input type="text" name="name" size="30" value="{$defplayer->display_name()}"></td>
</tr>
<tr>
	<td colspan="2">(Please enter as Firstname Lastname using nearest English letters if needed)</td>
</tr>
<tr>
	<td>Club</td>
	<td>
EOT;
$defplayer->clubopt("club_sel");
$qclub = htmlspecialchars($defplayer->Club);
print <<<EOT
</td></tr><tr><td>(Enter if not on drop-down)</td><td><input type="text" name="club" value="$qclub" size="30" onchange="clubedited"></td></tr>
<tr><td>Country</td><td>
EOT;
$defplayer->countryopt("country_sel");
$qcountry = htmlspecialchars($defplayer->Country);
print <<<EOT
</td></tr><tr><td>(Enter if not on drop-down)</td><td><input type="text" name="country" value="$qcountry" size="30" onchange="countryedited"></td></tr>
<tr><td>Rank</td><td>
EOT;
$defplayer->rankopt();
print <<<EOT
</td></tr>
<tr>
	<td>Email</td>
	<td><input type="text" name="email" size="30" value="{$defplayer->display_email_nolink()}"></td>
</tr>

EOT;
if ($tourn->Nonbga != 0)  {
	print <<<EOT
<tr><td>BGA Memb</td><td>

EOT;
	$defplayer->bgaopt(strlen($userid) == 0, "calccost");
	print "</td></tr>\n";
}

if  ($tourn->Concess1 != 0  || $tourn->Concess2 != 0)  {
	$rows = 2;
	if  ($tourn->Concess1 != 0  && $tourn->Concess2 != 0)
		$rows = 3;
	$sck = ' checked="checked"';
	$c1ck = $c2ck = "";
	if ($preventry->Concess1) {
		$c1ck = ' checked="checked"';
		$sck = "";
	}
	elseif($preventry->Concess2) {
		$c2ck = ' checked="checked"';
		$sck = "";
	}
	print <<<EOT
<tr>
	<td rowspan="$rows">Entry Type</td>
	<td><input type="radio" name="concess" value="std"$sck onchange="calccost();" />Standard</td>
</tr>

EOT;
	if ($tourn->Concess1 != 0)
		print <<<EOT
<tr>
	<td><input type="radio" name="concess" value="C1"$c1ck onchange="calccost();" />{$tourn->display_concess1name()}</td>
</tr>

EOT;
	if ($tourn->Concess2 != 0)
		print <<<EOT
<tr>
	<td><input type="radio" name="concess" value="C2"$c2ck onchange="calccost();" />{$tourn->display_concess2name()}</td>
</tr>

EOT;
}
if  ($tourn->Lunch > 0)  {
	$lck = "";
	if ($preventry->Lunch)
		$lck = ' checked="checked"';
	print <<<EOT
<tr>
	<td>Lunch (add &pound;{$tourn->display_lunch()})</td>
	<td><input type="checkbox" name="lunch"$lck onchange="calccost();"></td>
</tr>

EOT;
}

if (strlen($tourn->Dinner) != 0)  {
	$dck = "";
	if ($preventry->Dinner)
		$dck = ' checked="checked"';
	print <<<EOT
<tr>
	<td>Joining for {$tourn->display_dinner()}</td>
	<td><input type="checkbox" name="dinner"$dck></td>
</tr>

EOT;
}

$pck = "";
if ($preventry->Privacy)
	$pck = ' checked="checked"';

print <<<EOT
<tr>
	<td>Do not list publicly</td>
	<td><input type="checkbox" name="privacy"$pck></td>
</tr>
<tr>
	<td>Cost</td>
	<td>&pound;<input name="cost" type="text" size="5" maxlength="5" value="{$tourn->display_basic_fee()}"></td>
</tr>

EOT;

include 'php/sumchallenge.php';
?>
<tr>
	<td>Click to enter</td>
	<td><input type="submit" value="Submit"></td>
</tr>
</table>
</form>
</div>
</div>
</body>
</html>
