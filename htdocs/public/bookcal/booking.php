<?php
/* Copyright (C) 2001-2002  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2001-2002  Jean-Louis Bergamo      <jlb@j1b.org>
 * Copyright (C) 2006-2013  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2012       Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2012       J. Fernando Lagrange    <fernando@demo-tic.org>
 * Copyright (C) 2018-2019  Frédéric France         <frederic.france@netlogic.fr>
 * Copyright (C) 2018       Alexandre Spangaro      <aspangaro@open-dsi.fr>
 * Copyright (C) 2021       Waël Almoman            <info@almoman.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *	\file       htdocs/public/partnership/new.php
 *	\ingroup    member
 *	\brief      Example of form to add a new member
 */

use Stripe\Event;

if (!defined('NOLOGIN')) {
	define("NOLOGIN", 1); // This means this output page does not require to be logged.
}
if (!defined('NOCSRFCHECK')) {
	define("NOCSRFCHECK", 1); // We accept to go on this page from external web site.
}
if (!defined('NOIPCHECK')) {
	define('NOIPCHECK', '1'); // Do not check IP defined into conf $dolibarr_main_restrict_ip
}
if (!defined('NOBROWSERNOTIF')) {
	define('NOBROWSERNOTIF', '1');
}
if (!defined('NOIPCHECK')) {
	define('NOIPCHECK', '1'); // Do not check IP defined into conf $dolibarr_main_restrict_ip
}

// For MultiCompany module.
// Do not use GETPOST here, function is not defined and define must be done before including main.inc.php
// TODO This should be useless. Because entity must be retrieve from object ref and not from url.
$entity = (!empty($_GET['entity']) ? (int) $_GET['entity'] : (!empty($_POST['entity']) ? (int) $_POST['entity'] : 1));
if (is_numeric($entity)) {
	define("DOLENTITY", $entity);
}

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/partnership/class/partnership.class.php';
require_once DOL_DOCUMENT_ROOT.'/partnership/class/partnership_type.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/agenda.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT.'/comm/action/class/cactioncomm.class.php';
require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncommreminder.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formactions.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/task.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';


// Init vars
$errmsg = '';
$num = 0;
$error = 0;
$backtopage = GETPOST('backtopage', 'alpha');
$action = GETPOST('action', 'aZ09');

// Load translation files
$langs->loadLangs(array("main", "members", "companies", "install", "other"));


// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('publicnewpartnershipcard', 'globalcard'));


$object = new ActionComm($db);
$cactioncomm = new CActionComm($db);
$contact = new Contact($db);
$formfile = new FormFile($db);
$formactions = new FormActions($db);
$extrafields = new ExtraFields($db);

$id = GETPOST('id', 'int');

print "<style>

:root {
    --calendar-bg-color: #ddd;
    --calendar-font-color: #000;
    --weekdays-border-bottom-color: #ddd;
    --calendar-date-hover-color: #505050;
    --calendar-current-date-color: #ddd;
    --calendar-today-color: linear-gradient(to bottom, #03a9f4, #2196f3);
    --calendar-today-innerborder-color: transparent;
    --calendar-nextprev-bg-color: transparent;
    --next-prev-arrow-color : #000;
    --calendar-border-radius: 30px;
    --calendar-prevnext-date-color: #484848
}


.calendar {
	position: relative;
	max-width: 800px;
	min-width: 400px;
	background-color: ;
	color: #000;
	margin: 20px auto;
	box-sizing: border-box;
	overflow: hidden;
	font-weight: normal;
	border-radius: var(--calendar-border-radius);
}

.calendar-inner {
	padding: 10px 10px;
}

.calendar .calendar-inner .calendar-body {
	display: grid;
	grid-template-columns: repeat(7, 1fr);
	text-align: center;
}

.calendar .calendar-inner .calendar-body div {
	padding: 4px;
	min-height: 30px;
	line-height: 30px;
	border: 1px solid transparent;
	margin: 10px 2px 0px;
}

.calendar .calendar-inner .calendar-body div:nth-child(-n+7) {
	border: 1px solid transparent;
	border-bottom: 1px solid var(--weekdays-border-bottom-color);
}

.calendar .calendar-inner .calendar-body div:nth-child(-n+7):hover {
	border: 1px solid transparent;
	border-bottom: 1px solid var(--weekdays-border-bottom-color);
}

.calendar .calendar-inner .calendar-body div>a {
	color: var(--calendar-font-color);
	text-decoration: none;
	display: flex;
	justify-content: center;
}

.calendar .calendar-inner .calendar-body div:hover {
border: 1px solid var(--calendar-date-hover-color);
border-radius: 4px;
}

.calendar .calendar-inner .calendar-body div.empty-dates:hover {
border: 1px solid transparent;
}

.calendar .calendar-inner .calendar-controls {
display: grid;
grid-template-columns: repeat(3, 1fr);
}

.calendar .calendar-inner .calendar-today-date {
display: grid;
text-align: center;
cursor: pointer;
margin: 3px 0px;
background: var(--calendar-current-date-color);
padding: 8px 0px;
border-radius: 10px;
width: 80%;
margin: auto;
}

.calendar .calendar-inner .calendar-controls .calendar-year-month {
display: flex;
min-width: 100px;
justify-content: space-evenly;
align-items: center;
}

.calendar .calendar-inner .calendar-controls .calendar-next {
text-align: right;
}

.calendar .calendar-inner .calendar-controls .calendar-year-month .calendar-year-label,
.calendar .calendar-inner .calendar-controls .calendar-year-month .calendar-month-label {
font-weight: 500;
font-size: 20px;
}

.calendar .calendar-inner .calendar-body .calendar-today {
background: var(--calendar-today-color);
border-radius: 4px;
}

.calendar .calendar-inner .calendar-body .calendar-today:hover {
border: 1px solid transparent;
}

.calendar .calendar-inner .calendar-body .calendar-today a {
outline: 2px solid var(--calendar-today-innerborder-color);
}

.calendar .calendar-inner .calendar-controls .calendar-next a,
.calendar .calendar-inner .calendar-controls .calendar-prev a {
color: var(--calendar-font-color);
font-family: arial, consolas, sans-serif;
font-size: 26px;
text-decoration: none;
padding: 4px 12px;
display: inline-block;
background: var(--calendar-nextprev-bg-color);
margin: 10px 0 10px 0;
}

.calendar .calendar-inner .calendar-controls .calendar-next a svg,
.calendar .calendar-inner .calendar-controls .calendar-prev a svg {
height: 20px;
width: 20px;
}

.calendar .calendar-inner .calendar-controls .calendar-next a svg path,
.calendar .calendar-inner .calendar-controls .calendar-prev a svg path{
fill: var(--next-prev-arrow-color);
}

.calendar .calendar-inner .calendar-body .prev-dates,
.calendar .calendar-inner .calendar-body .next-dates {
color: var(--calendar-prevnext-date-color);
}

.calendar .calendar-inner .calendar-body .prev-dates:hover,
.calendar .calendar-inner .calendar-body .next-dates:hover {
border: 1px solid transparent;
pointer-events: none;
}


</style>";



print '<script>
alert("wdymean");
document.addEventListener("DOMContentLoaded", function(event) { 	
	function CalendarControl() {
		const calendar = new Date();
		const calendarControl = {
			localDate: new Date(),
			prevMonthLastDate: null,
			calWeekDays: ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"],
			calMonthName: [
				"Jan",
				"Feb",
				"Mar",
				"Apr",
				"May",
				"Jun",
				"Jul",
				"Aug",
				"Sep",
				"Oct",
				"Nov",
				"Dec",
			],
			daysInMonth: function (month, year) {
				return new Date(year, month, 0).getDate();
			},
			firstDay: function () {
				return new Date(calendar.getFullYear(), calendar.getMonth(), 1);
			},
			lastDay: function () {
				return new Date(calendar.getFullYear(), calendar.getMonth() + 1, 0);
			},
			firstDayNumber: function () {
				return calendarControl.firstDay().getDay() + 1;
			},
			lastDayNumber: function () {
				return calendarControl.lastDay().getDay() + 1;
			},
			getPreviousMonthLastDate: function () {
				let lastDate = new Date(
					calendar.getFullYear(),
					calendar.getMonth(),
					0
				).getDate();
				return lastDate;
			},
			navigateToPreviousMonth: function () {
				calendar.setMonth(calendar.getMonth() - 1);
				calendarControl.attachEventsOnNextPrev();
			},
			navigateToNextMonth: function () {
				calendar.setMonth(calendar.getMonth() + 1);
				calendarControl.attachEventsOnNextPrev();
			},
			navigateToCurrentMonth: function () {
				let currentMonth = calendarControl.localDate.getMonth();
				let currentYear = calendarControl.localDate.getFullYear();
				calendar.setMonth(currentMonth);
				calendar.setYear(currentYear);
				calendarControl.attachEventsOnNextPrev();
			},
			displayYear: function () {
				let yearLabel = document.querySelector(
					".calendar .calendar-year-label"
				);
				yearLabel.innerHTML = calendar.getFullYear();
			},
			displayMonth: function () {
				let monthLabel = document.querySelector(
					".calendar .calendar-month-label"
				);
				monthLabel.innerHTML =
					calendarControl.calMonthName[calendar.getMonth()];
			},
			selectDate: function (e) {
				console.log(
					`${e.target.textContent} ${
						calendarControl.calMonthName[calendar.getMonth()]
					} ${calendar.getFullYear()}`
				);
			},
			plotSelectors: function () {				
				document.querySelector(
					".calendar"
				).innerHTML += `<div class="calendar-inner"><div class="calendar-controls">
          <div class="calendar-prev"><a href="#"><svg xmlns="http://www.w3.org/2000/svg" width="128" height="128" viewBox="0 0 128 128"><path fill="#666" d="M88.2 3.8L35.8 56.23 28 64l7.8 7.78 52.4 52.4 9.78-7.76L45.58 64l52.4-52.4z"/></svg></a></div>
          <div class="calendar-year-month">
          <div class="calendar-month-label"></div>
          <div>-</div>
          <div class="calendar-year-label"></div>
          </div>
          <div class="calendar-next"><a href="#"><svg xmlns="http://www.w3.org/2000/svg" width="128" height="128" viewBox="0 0 128 128"><path fill="#666" d="M38.8 124.2l52.4-52.42L99 64l-7.77-7.78-52.4-52.4-9.8 7.77L81.44 64 29 116.42z"/></svg></a></div>
          </div>
          <div class="calendar-today-date">Today: 
            ${calendarControl.calWeekDays[calendarControl.localDate.getDay()]}, 
            ${calendarControl.localDate.getDate()}, 
            ${
							calendarControl.calMonthName[calendarControl.localDate.getMonth()]
						} 
            ${calendarControl.localDate.getFullYear()}
          </div>
          <div class="calendar-body"></div></div>`;
			},
			plotDayNames: function () {
				for (let i = 0; i < calendarControl.calWeekDays.length; i++) {
					document.querySelector(
						".calendar .calendar-body"
					).innerHTML += `<div>${calendarControl.calWeekDays[i]}</div>`;
				}
			},
			plotDates: function () {
				document.querySelector(".calendar .calendar-body").innerHTML = "";
				calendarControl.plotDayNames();
				calendarControl.displayMonth();
				calendarControl.displayYear();
				let count = 1;
				let prevDateCount = 0;

				calendarControl.prevMonthLastDate =
					calendarControl.getPreviousMonthLastDate();
				let prevMonthDatesArray = [];
				let calendarDays = calendarControl.daysInMonth(
					calendar.getMonth() + 1,
					calendar.getFullYear()
				);
				// dates of current month
				for (let i = 1; i < calendarDays; i++) {
					if (i < calendarControl.firstDayNumber()) {
						prevDateCount += 1;
						document.querySelector(
							".calendar .calendar-body"
						).innerHTML += `<div class="prev-dates"></div>`;
						prevMonthDatesArray.push(calendarControl.prevMonthLastDate--);
					} else {
						document.querySelector(
							".calendar .calendar-body"
						).innerHTML += `<div class="number-item" data-num=${count}><a class="dateNumber" href="#">${count++}</a></div>`;
					}
				}
				//remaining dates after month dates
				for (let j = 0; j < prevDateCount + 1; j++) {
					document.querySelector(
						".calendar .calendar-body"
					).innerHTML += `<div class="number-item" data-num=${count}><a class="dateNumber" href="#">${count++}</a></div>`;
				}
				calendarControl.highlightToday();
				calendarControl.plotPrevMonthDates(prevMonthDatesArray);
				calendarControl.plotNextMonthDates();
			},
			attachEvents: function () {
				let prevBtn = document.querySelector(".calendar .calendar-prev a");
				let nextBtn = document.querySelector(".calendar .calendar-next a");
				let todayDate = document.querySelector(
					".calendar .calendar-today-date"
				);
				let dateNumber = document.querySelectorAll(".calendar .dateNumber");
				prevBtn.addEventListener(
					"click",
					calendarControl.navigateToPreviousMonth
				);
				nextBtn.addEventListener("click", calendarControl.navigateToNextMonth);
				todayDate.addEventListener(
					"click",
					calendarControl.navigateToCurrentMonth
				);
				for (var i = 0; i < dateNumber.length; i++) {
					dateNumber[i].addEventListener(
						"click",
						calendarControl.selectDate,
						false
					);
				}
			},
			highlightToday: function () {
				let currentMonth = calendarControl.localDate.getMonth() + 1;
				let changedMonth = calendar.getMonth() + 1;
				let currentYear = calendarControl.localDate.getFullYear();
				let changedYear = calendar.getFullYear();
				if (
					currentYear === changedYear &&
					currentMonth === changedMonth &&
					document.querySelectorAll(".number-item")
				) {
					document
						.querySelectorAll(".number-item")
						[calendar.getDate() - 1].classList.add("calendar-today");
				}
			},
			plotPrevMonthDates: function (dates) {
				dates.reverse();
				for (let i = 0; i < dates.length; i++) {
					if (document.querySelectorAll(".prev-dates")) {
						document.querySelectorAll(".prev-dates")[i].textContent = dates[i];
					}
				}
			},
			plotNextMonthDates: function () {
				let childElemCount =
					document.querySelector(".calendar-body").childElementCount;
				//7 lines
				if (childElemCount > 42) {
					let diff = 49 - childElemCount;
					calendarControl.loopThroughNextDays(diff);
				}

				//6 lines
				if (childElemCount > 35 && childElemCount <= 42) {
					let diff = 42 - childElemCount;
					calendarControl.loopThroughNextDays(42 - childElemCount);
				}
			},
			loopThroughNextDays: function (count) {
				if (count > 0) {
					for (let i = 1; i <= count; i++) {
						document.querySelector(
							".calendar-body"
						).innerHTML += `<div class="next-dates">${i}</div>`;
					}
				}
			},
			attachEventsOnNextPrev: function () {
				calendarControl.plotDates();
				calendarControl.attachEvents();
			},
			init: function () {
				calendarControl.plotSelectors();
				calendarControl.plotDates();
				calendarControl.attachEvents();
			},
		};
		calendarControl.init();
	}

	const calendarControl = new CalendarControl();	

})
</script>';

/**
 * @param $availability_id The availability ID.
 * @return availability of the availability ID.
 */
function getAvailability($availability_id)
{
	global $db;
	$sql = "SELECT * FROM ".MAIN_DB_PREFIX."llx_bookcal_availabilities WHERE id = ". $availability_id;
	$res = $db->query($sql);
	$i = 0;
	$availability = array();
	while ($obj = $db->fetch_object($res)) {
		$availability[$i]['id'] = $obj->id;
		$availability[$i]['label'] = $obj->label;
		$i++;
	}
	return $availability;
}
//$availability = getAvailability($id);
$user->loadDefaultValues();

//var_dump($availability);


/**
 * Show header for new partnership
 *
 * @param 	string		$title				Title
 * @param 	string		$head				Head array
 * @param 	int    		$disablejs			More content into html header
 * @param 	int    		$disablehead		More content into html header
 * @param 	array  		$arrayofjs			Array of complementary js files
 * @param 	array  		$arrayofcss			Array of complementary css files
 * @return	void
 */
function llxHeaderVierge($title, $head = "", $disablejs = 0, $disablehead = 0, $arrayofjs = '', $arrayofcss = '')
{
	global $user, $conf, $langs, $mysoc;

	top_htmlhead($head, $title, $disablejs, $disablehead, $arrayofjs, $arrayofcss); // Show html headers

	print '<body id="mainbody" class="publicnewmemberform">';

	// Define urllogo
	$urllogo = DOL_URL_ROOT.'/theme/common/login_logo.png';

	if (!empty($mysoc->logo_small) && is_readable($conf->mycompany->dir_output.'/logos/thumbs/'.$mysoc->logo_small)) {
		$urllogo = DOL_URL_ROOT.'/viewimage.php?cache=1&amp;modulepart=mycompany&amp;file='.urlencode('logos/thumbs/'.$mysoc->logo_small);
	} elseif (!empty($mysoc->logo) && is_readable($conf->mycompany->dir_output.'/logos/'.$mysoc->logo)) {
		$urllogo = DOL_URL_ROOT.'/viewimage.php?cache=1&amp;modulepart=mycompany&amp;file='.urlencode('logos/'.$mysoc->logo);
	} elseif (is_readable(DOL_DOCUMENT_ROOT.'/theme/dolibarr_logo.svg')) {
		$urllogo = DOL_URL_ROOT.'/theme/dolibarr_logo.svg';
	}

	print '<div class="center">';

	// Output html code for logo
	if ($urllogo) {
		print '<div class="backgreypublicpayment">';
		print '<div class="logopublicpayment">';
		print '<img id="dolpaymentlogo" src="'.$urllogo.'">';
		print '</div>';
		if (empty($conf->global->MAIN_HIDE_POWERED_BY)) {
			print '<div class="poweredbypublicpayment opacitymedium right"><a class="poweredbyhref" href="https://www.dolibarr.org?utm_medium=website&utm_source=poweredby" target="dolibarr" rel="noopener">'.$langs->trans("PoweredBy").'<br><img class="poweredbyimg" src="'.DOL_URL_ROOT.'/theme/dolibarr_logo.svg" width="80px"></a></div>';
		}
		print '</div>';
	}

	if (!empty($conf->global->PARTNERSHIP_IMAGE_PUBLIC_REGISTRATION)) {
		print '<div class="backimagepublicregistration">';
		print '<img id="idPARTNERSHIP_IMAGE_PUBLIC_INTERFACE" src="'.$conf->global->PARTNERSHIP_IMAGE_PUBLIC_REGISTRATION.'">';
		print '</div>';
	}

	print '</div>';

	print '<div class="divmainbodylarge">';
}

/**
 * Show footer for new member
 *
 * @return	void
 */
function llxFooterVierge()
{
	print '</div>';

	printCommonFooter('public');

	print "</body>\n";
	print "</html>\n";
}


/*
 * Actions
 */
$parameters = array();
// Note that $action and $object may have been modified by some hooks
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action);
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

// Action called when page is submitted
if (empty($reshook) && $action == 'add') {
	$error = 0;
	$urlback = '';

	$db->begin();

	/*if (GETPOST('typeid') <= 0) {
		$error++;
		$errmsg .= $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Type"))."<br>\n";
	}*/
	if (!GETPOST('lastname')) {
		$error++;
		$errmsg .= $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Lastname"))."<br>\n";
	}
	if (!GETPOST('firstname')) {
		$error++;
		$errmsg .= $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Firstname"))."<br>\n";
	}
	if (empty(GETPOST('email'))) {
		$error++;
		$errmsg .= $langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('Email'))."<br>\n";
	} elseif (GETPOST("email") && !isValidEmail(GETPOST("email"))) {
		$langs->load('errors');
		$error++;
		$errmsg .= $langs->trans("ErrorBadEMail", GETPOST("email"))."<br>\n";
	}

	$public = GETPOSTISSET('public') ? 1 : 0;

	if (!$error) {
		//$partnership = new Partnership($db);
		$events = new Events($db);


		// We try to find the thirdparty or the member
		if (getDolGlobalString('PARTNERSHIP_IS_MANAGED_FOR', 'thirdparty') == 'thirdparty') {
			$event->fk_member = 0;
		} elseif (getDolGlobalString('PARTNERSHIP_IS_MANAGED_FOR', 'thirdparty') == 'member') {
			$event->fk_soc = 0;
		}

		$events->statut      = -1;
		$events->firstname   = GETPOST('firstname');
		$events->lastname    = GETPOST('lastname');
		$events->address     = GETPOST('address');
		$events->zip         = GETPOST('zipcode');
		$events->town        = GETPOST('town');
		$events->email       = GETPOST('email');
		$events->country_id  = GETPOST('country_id', 'int');
		$events->state_id    = GETPOST('state_id', 'int');
		//$partnership->typeid      = $conf->global->PARTNERSHIP_NEWFORM_FORCETYPE ? $conf->global->PARTNERSHIP_NEWFORM_FORCETYPE : GETPOST('typeid', 'int');
		$event->note_private = GETPOST('note_private');

		// Fill array 'array_options' with data from add form
		$extrafields->fetch_name_optionals_label($partnership->table_element);
		$ret = $extrafields->setOptionalsFromPost(null, $partnership);
		if ($ret < 0) {
			$error++;
		}

		if (!$error) {
			$result = $event->create($user);
			if ($result > 0) {
				$db->commit();
				$urlback = DOL_URL_ROOT.'/public/partnership/new.php?action=confirm&id='.$event->id;
				header('Location: '.$urlback);
				exit;
			} else {
				$db->rollback();
				$errmsg = $event->error;
				$error++;
			}
		} else {
			$error++;
			$errmsg .= join('<br>', $event->errors);
		}
	}
}

// Action called after a submitted was send and member created successfully
// If PARTNERSHIP_URL_REDIRECT_SUBSCRIPTION is set to url we never go here because a redirect was done to this url.
// backtopage parameter with an url was set on member submit page, we never go here because a redirect was done to this url.
if (empty($reshook) && $action == 'added') {
	llxHeaderVierge($langs->trans("NewPartnershipForm"));

	// Si on a pas ete redirige
	print '<br><br>';
	print '<div class="center">';
	print $langs->trans("NewPartnershipbyWeb");
	print '</div>';

	llxFooterVierge();

	exit;
}



/*
 * View
 */

$form = new Form($db);
$formcompany = new FormCompany($db);

$extrafields->fetch_name_optionals_label($partnership->table_element); // fetch optionals attributes and labels


llxHeaderVierge($langs->trans("NewBookingRequest"));


print load_fiche_titre($langs->trans("NewBookingRequest"), '', '', 0, 0, 'center');



// View

// Add new Events form
$contact = new Contact($db);
if ($id > 0) {
	$socpeopleassigned = GETPOST("socpeopleassigned", 'array');
	if (!empty($socpeopleassigned[0])) {
		$result = $contact->fetch($socpeopleassigned[0]);
		if ($result < 0) {
			dol_print_error($db, $contact->error);
		}
	}

		dol_set_focus("#label");

	if (!empty($conf->use_javascript_ajax)) {
		print "\n".'<script type="text/javascript">';
		print '$(document).ready(function () {
					    
						function setdatefields()
						{
							if ($("#fullday:checked").val() == null) {
								$(".fulldaystarthour").removeAttr("disabled");
								$(".fulldaystartmin").removeAttr("disabled");
								$(".fulldayendhour").removeAttr("disabled");
								$(".fulldayendmin").removeAttr("disabled");
								$("#p2").removeAttr("disabled");
							} else {
								$(".fulldaystarthour").prop("disabled", true).val("00");
								$(".fulldaystartmin").prop("disabled", true).val("00");
								$(".fulldayendhour").prop("disabled", true).val("23");
								$(".fulldayendmin").prop("disabled", true).val("59");
								$("#p2").removeAttr("disabled");
							}
						}
						$("#fullday").change(function() {
							console.log("setdatefields");
							setdatefields();
						});

						$("#selectcomplete").change(function() {
							console.log("we change the complete status - set the doneby");
							if ($("#selectcomplete").val() == 100) {
								if ($("#doneby").val() <= 0) $("#doneby").val(\''.((int) $user->id).'\');
							}
							if ($("#selectcomplete").val() == 0) {
								$("#doneby").val(-1);
							}
						});

						$("#actioncode").change(function() {
							if ($("#actioncode").val() == \'AC_RDV\') $("#dateend").addClass("fieldrequired");
							else $("#dateend").removeClass("fieldrequired");
						});
						$("#aphour,#apmin").change(function() {
							if ($("#actioncode").val() == \'AC_RDV\') {
								console.log("Start date was changed, we modify end date "+(parseInt($("#aphour").val()))+" "+$("#apmin").val()+" -> "+("00" + (parseInt($("#aphour").val()) + 1)).substr(-2,2));
								$("#p2hour").val(("00" + (parseInt($("#aphour").val()) + 1)).substr(-2,2));
								$("#p2min").val($("#apmin").val());
								$("#p2day").val($("#apday").val());
								$("#p2month").val($("#apmonth").val());
								$("#p2year").val($("#apyear").val());
								$("#p2").val($("#ap").val());
							}
						});
						if ($("#actioncode").val() == \'AC_RDV\') $("#dateend").addClass("fieldrequired");
						else $("#dateend").removeClass("fieldrequired");
						setdatefields();
				})';
		print '</script>'."\n";
	}
		print '<form name="formaction" action="'.$_SERVER['PHP_SELF'].'" method="POST">';
		print '<input type="hidden" name="token" value="'.newToken().'">';
		print '<input type="hidden" name="action" value="add">';
		print '<input type="hidden" name="donotclearsession" value="1">';
		print '<input type="hidden" name="page_y" value="">';
	if ($backtopage) {
		print '<input type="hidden" name="backtopage" value="'.($backtopage != '1' ? $backtopage : dol_htmlentities($_SERVER["HTTP_REFERER"])).'">';
	}
	if (empty($conf->global->AGENDA_USE_EVENT_TYPE)) {
		print '<input type="hidden" name="actioncode" value="'.dol_getIdFromCode($db, 'AC_OTH', 'c_actioncomm').'">';
	}

	if (GETPOST("actioncode", 'aZ09') == 'AC_RDV') {
		print load_fiche_titre($langs->trans("AddActionRendezVous"), '', 'title_agenda');
	} else {
		print load_fiche_titre($langs->trans("AddAnAction"), '', 'title_agenda');
	}

		print dol_get_fiche_head();

		print '<table class="border centpercent">';

		// Type of event
	if (!empty($conf->global->AGENDA_USE_EVENT_TYPE)) {
		print '<tr><td class="titlefieldcreate"><span class="fieldrequired">'.$langs->trans("Type").'</span></b></td><td>';
		$default = (empty($conf->global->AGENDA_USE_EVENT_TYPE_DEFAULT) ? 'AC_RDV' : $conf->global->AGENDA_USE_EVENT_TYPE_DEFAULT);
		print img_picto($langs->trans("ActionType"), 'square', 'class="fawidth30 inline-block" style="color: #ddd;"');
		print $formactions->select_type_actions(GETPOSTISSET("actioncode") ? GETPOST("actioncode", 'aZ09') : ($object->type_code ? $object->type_code : $default), "actioncode", "systemauto", 0, -1, 0, 1);	// TODO Replace 0 with -2 in onlyautoornot
		print '</td></tr>';
	}

		// Title
		print '<tr><td'.(empty($conf->global->AGENDA_USE_EVENT_TYPE) ? ' class="fieldrequired titlefieldcreate"' : '').'>'.$langs->trans("Label").'</td><td><input type="text" id="label" name="label" class="soixantepercent" value="'.GETPOST('label').'"></td></tr>';

		// Full day
		print '<tr><td><span class="fieldrequired">'.$langs->trans("Date").'</span></td><td class="valignmiddle height30 small"><input type="checkbox" id="fullday" name="fullday" '.(GETPOST('fullday') ? ' checked' : '').'><label for="fullday">'.$langs->trans("EventOnFullDay").'</label>';

		// Recurring event
		$userepeatevent = ($conf->global->MAIN_FEATURES_LEVEL == 2 ? 1 : 0);
	if ($userepeatevent) {
		// Repeat
		//print '<tr><td></td><td colspan="3" class="opacitymedium">';

		$selectedrecurrulefreq = 'no';
		$selectedrecurrulebymonthday = '';
		$selectedrecurrulebyday = '';
		if ($object->recurrule && preg_match('/FREQ=([A-Z]+)/i', $object->recurrule, $reg)) {
			$selectedrecurrulefreq = $reg[1];
		}
		if ($object->recurrule && preg_match('/FREQ=MONTHLY.*BYMONTHDAY=(\d+)/i', $object->recurrule, $reg)) {
			$selectedrecurrulebymonthday = $reg[1];
		}
		if ($object->recurrule && preg_match('/FREQ=WEEKLY.*BYDAY(\d+)/i', $object->recurrule, $reg)) {
			$selectedrecurrulebyday = $reg[1];
		}
		print $form->selectarray('recurrulefreq', $arrayrecurrulefreq, $selectedrecurrulefreq, 0, 0, 0, '', 0, 0, 0, '', 'marginrightonly');
		// If recurrulefreq is MONTHLY
		print '<div class="hidden marginrightonly inline-block repeateventBYMONTHDAY">';
		print $langs->trans("DayOfMonth").': <input type="input" size="2" name="BYMONTHDAY" value="'.$selectedrecurrulebymonthday.'">';
		print '</div>';
		// If recurrulefreq is WEEKLY
		print '<div class="hidden marginrightonly inline-block repeateventBYDAY">';
		print $langs->trans("DayOfWeek").': <input type="input" size="4" name="BYDAY" value="'.$selectedrecurrulebyday.'">';
		print '</div>';
		print '<script type="text/javascript">
		jQuery(document).ready(function() {
			
						function init_repeat()
						{
							if (jQuery("#recurrulefreq").val() == \'MONTHLY\')
							{
								jQuery(".repeateventBYMONTHDAY").css("display", "inline-block");		/* use this instead of show because we want inline-block and not block */
								jQuery(".repeateventBYDAY").hide();
							}
							else if (jQuery("#recurrulefreq").val() == \'WEEKLY\')
							{
								jQuery(".repeateventBYMONTHDAY").hide();
								jQuery(".repeateventBYDAY").css("display", "inline-block");		/* use this instead of show because we want inline-block and not block */
							}
							else
							{
								jQuery(".repeateventBYMONTHDAY").hide();
								jQuery(".repeateventBYDAY").hide();
							}
						}
						init_repeat();
						jQuery("#recurrulefreq").change(function() {
							init_repeat();
						});
					});
					</script>';
		print '</div>';
		//print '</td></tr>';
	}

		print '</td></tr>';

		$datep = ($datep ? $datep : (is_null($object->datep) ? '' : $object->datep));
	if (GETPOST('datep', 'int', 1)) {
		$datep = dol_stringtotime(GETPOST('datep', 'int', 1), 'tzuser');
	}
		$datef = ($datef ? $datef : $object->datef);
	if (GETPOST('datef', 'int', 1)) {
		$datef = dol_stringtotime(GETPOST('datef', 'int', 1), 'tzuser');
	}
	if (empty($datef) && !empty($datep)) {
		if (GETPOST("actioncode", 'aZ09') == 'AC_RDV' || empty($conf->global->AGENDA_USE_EVENT_TYPE_DEFAULT)) {
			$datef = dol_time_plus_duree($datep, (empty($conf->global->AGENDA_AUTOSET_END_DATE_WITH_DELTA_HOURS) ? 1 : $conf->global->AGENDA_AUTOSET_END_DATE_WITH_DELTA_HOURS), 'h');
		}
	}

		// Date start
		print '<tr><td class="nowrap">';
		/*
		print '<span class="fieldrequired">'.$langs->trans("DateActionStart").'</span>';
		print ' - ';
		print '<span id="dateend"'.(GETPOST("actioncode", 'aZ09') == 'AC_RDV' ? ' class="fieldrequired"' : '').'>'.$langs->trans("DateActionEnd").'</span>';
		*/
		print '</td><td>';
	if (GETPOST("afaire") == 1) {
		print $form->selectDate($datep, 'ap', 1, 1, 0, "action", 1, 2, 0, 'fulldaystart', '', '', '', 1, '', '', 'tzuserrel'); // Empty value not allowed for start date and hours if "todo"
	} else {
		print $form->selectDate($datep, 'ap', 1, 1, 1, "action", 1, 2, 0, 'fulldaystart', '', '', '', 1, '', '', 'tzuserrel');
	}
		print ' <span class="hideonsmartphone">&nbsp; &nbsp; - &nbsp; &nbsp;</span> ';
	if (GETPOST("afaire") == 1) {
		print $form->selectDate($datef, 'p2', 1, 1, 1, "action", 1, 0, 0, 'fulldayend', '', '', '', 1, '', '', 'tzuserrel');
	} else {
		print $form->selectDate($datef, 'p2', 1, 1, 1, "action", 1, 0, 0, 'fulldayend', '', '', '', 1, '', '', 'tzuserrel');
	}
		print '</td></tr>';

		// Date
		print '<tr> <td class="fieldrequired titlefieldcreate nowrap">'. $langs->trans("Calendar") . '</td> <td> <div class="calendar"></div>
		</td> </tr>';


		// Description
		print '<tr><td class="tdtop">'.$langs->trans("Description").'</td><td>';
		require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
		$doleditor = new DolEditor('note', (GETPOSTISSET('note') ? GETPOST('note', 'restricthtml') : $object->note_private), '', 120, 'dolibarr_notes', 'In', true, true, $conf->fckeditor->enabled, ROWS_4, '90%');
		$doleditor->Create();
		print '</td></tr>';

		// Other attributes
		$parameters = array();
		$reshook = $hookmanager->executeHooks('formObjectOptions', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
		print $hookmanager->resPrint;
	if (empty($reshook)) {
		print $object->showOptionals($extrafields, 'create', $parameters);
	}

		print '</table>';


	if (getDolGlobalString('AGENDA_REMINDER_EMAIL') || getDolGlobalString('AGENDA_REMINDER_BROWSER')) {
		//checkbox create reminder
		print '<hr>';
		print '<br>';
		print '<label for="addreminder">'.img_picto('', 'bell', 'class="pictofixedwidth"').$langs->trans("AddReminder").'</label> <input type="checkbox" id="addreminder" name="addreminder"><br><br>';

		print '<div class="reminderparameters" style="display: none;">';

		//print '<hr>';
		//print load_fiche_titre($langs->trans("AddReminder"), '', '');

		print '<table class="border centpercent">';

		//Reminder
		print '<tr><td class="titlefieldcreate nowrap">'.$langs->trans("ReminderTime").'</td><td colspan="3">';
		print '<input class="width50" type="number" name="offsetvalue" value="'.(GETPOSTISSET('offsetvalue') ? GETPOST('offsetvalue', 'int') : '15').'"> ';
		print $form->selectTypeDuration('offsetunit', 'i', array('y', 'm'));
		print '</td></tr>';

		//Reminder Type
		print '<tr><td class="titlefieldcreate nowrap">'.$langs->trans("ReminderType").'</td><td colspan="3">';
		print $form->selectarray('selectremindertype', $TRemindTypes, '', 0, 0, 0, '', 0, 0, 0, '', 'minwidth200 maxwidth500', 1);
		print '</td></tr>';

		//Mail Model
		if (getDolGlobalString('AGENDA_REMINDER_EMAIL')) {
			print '<tr><td class="titlefieldcreate nowrap">'.$langs->trans("EMailTemplates").'</td><td colspan="3">';
			print $form->selectModelMail('actioncommsend', 'actioncomm_send', 1, 1);
			print '</td></tr>';
		}

		print '</table>';
		print '</div>';

		print "\n".'<script type="text/javascript">';
		print '$(document).ready(function () {
							$("#addreminder").click(function(){
								console.log("Click on addreminder");
								if (this.checked) {
									$(".reminderparameters").show();
								} else {
									$(".reminderparameters").hide();
								}
								$("#selectremindertype").select2("destroy");
								$("#selectremindertype").select2();
								$("#select_offsetunittype_duration").select2("destroy");
								$("#select_offsetunittype_duration").select2();
							});

							$("#selectremindertype").change(function(){
								console.log("Change on selectremindertype");
								var selected_option = $("#selectremindertype option:selected").val();
								if(selected_option == "email") {
									$("#select_actioncommsendmodel_mail").closest("tr").show();
								} else {
									$("#select_actioncommsendmodel_mail").closest("tr").hide();
								};
							});
					})';
		print '</script>'."\n";
	}

		print dol_get_fiche_end();

		print $form->buttonsSaveCancel("Add");

		print "</form>";


	llxFooterVierge();
}



$db->close();
