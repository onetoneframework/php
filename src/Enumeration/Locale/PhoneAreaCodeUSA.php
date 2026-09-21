<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Enumeration;

/**
 * Enumeration class for United States phone area codes.
 * Contains regional and city phone area codes.
 */
abstract class PhoneAreaCodeUSA
{
	// New England
	public const BOSTON = "617";
	public const PROVIDENCE = "401";
	public const HARTFORD = "860";
	public const VERMONT = "802";
	public const MAINE = "207";

	// New York
	public const NEW_YORK = "212";
	public const BROOKLYN = "718";
	public const QUEENS = "347";
	public const BUFFALO = "716";
	public const ALBANY = "518";

	// Mid-Atlantic
	public const PHILADELPHIA = "215";
	public const PITTSBURGH = "412";
	public const NEW_JERSEY = "201";
	public const NEWARK = "973";
	public const WASHINGTON_DC = "202";

	// Southeast
	public const ATLANTA = "404";
	public const CHARLOTTE = "704";
	public const MIAMI = "305";
	public const TAMPA = "813";
	public const ORLANDO = "407";
	public const NASHVILLE = "615";
	public const MEMPHIS = "901";
	public const NEW_ORLEANS = "504";

	// Midwest
	public const CHICAGO = "312";
	public const DETROIT = "313";
	public const MINNEAPOLIS = "612";
	public const KANSAS_CITY = "816";
	public const ST_LOUIS = "314";
	public const COLUMBUS = "614";
	public const CINCINNATI = "513";
	public const DALLAS = "214";

	// Southwest
	public const HOUSTON = "713";
	public const AUSTIN = "512";
	public const SAN_ANTONIO = "210";
	public const PHOENIX = "602";
	public const DENVER = "303";
	public const ALBUQUERQUE = "505";

	// West Coast
	public const LOS_ANGELES = "310";
	public const SAN_DIEGO = "619";
	public const SAN_FRANCISCO = "415";
	public const OAKLAND = "510";
	public const SEATTLE = "206";
	public const PORTLAND = "503";
}
