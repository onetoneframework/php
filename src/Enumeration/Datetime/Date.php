<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Enumeration\DateTime;

/**
 * Date Enumeration
 */
enum Date
{
    // =========================================================================
    // JULIAN / GREGORIAN CONVERSION
    // =========================================================================

    /** JDN of first day of the Gregorian calendar (1582-10-15) */
    public const JULIAN_TO_GREGORIAN_TRANSITION_JD = 2299161;
    public const JULIAN_DATE_GREGORIAN_START = 1867216.25;
    public const JD_JULIAN_TO_GREG_OFFSET = 1867216.25;
    public const JULIAN_MONTH_OFFSET = 12;
    public const JULIAN_YEAR_DAYS_FACTOR = 365.25;
    public const JULIAN_CENTURY_DAYS = 36524.25;
    public const JULIAN_MONTH_DAYS_FACTOR = 30.6001;
    /** JD of 1582-10-15 00:00 UT (Gregorian reform start, half-day shifted) */
    public const JULIAN_GREGORIAN_START_JD = 2299160.5;
    /** JD of 1582-10-15 00:00 UT (integer form used in algorithm) */
    public const JD_GREGORIAN_START = 2299160.0;

    // Meeus Algorithm 1 helper constants
    public const JD_YEAR_OFFSET_AFTER_FEB = 4716;
    public const JD_YEAR_OFFSET_BEFORE_MAR = 4715;
    public const JD_DAY_OFFSET = 122.1;
    public const JD_EPOCH_OFFSET = 1524.0;
    public const JULIAN_MONTH_DAYS = 30.6001;
    public const JULIAN_YEAR_DAYS = 365.25;
    public const JULIAN_EPOCH_OFFSET = 1720994.5;
    public const JULIAN_CENTURY_DIVISOR = 100.0;
    public const JULIAN_GREGORIAN_CORR_BASE = 2.0;
    public const JULIAN_GREGORIAN_CORR_DIV = 4.0;

    // =========================================================================
    // REFERENCE EPOCHS AND TIME SCALES
    // =========================================================================

    /**
     * J2000.0 epoch: 2000-01-01 12:00 TT
     * @var float
     */
    public const JD_J2000 = 2451545.0;

    /** Days per Julian century (used for T = (JD - JD_J2000) / DAYS_PER_JULIAN_CENTURY) */
    public const DAYS_PER_JULIAN_CENTURY = 36525.0;

    // ΔT: TT − UT1
    /** TT = TAI + TT_TAI_OFFSET_SEC */
    public const TT_TAI_OFFSET_SEC = 32.184;
    /** Anchor year of the unified quadratic ΔT model: ΔT ≈ −20 + 32·u², u=(Y−1820)/100 */
    public const DELTA_T_ANCHOR_YEAR = 1820;
    /** Constant term of quadratic ΔT model (seconds) */
    public const DELTA_T_CONST_SEC = -20.0;
    /** Quadratic coefficient of ΔT model (seconds per century²) */
    public const DELTA_T_QUADRATIC_COEFF = 32.0;
    /** Approximate ΔT in early 2026 (seconds) */
    public const DELTA_T_2026_SEC = 69.0;

    // =========================================================================
    // §3  TRADITIONAL TIBETAN CALENDAR — EPOCH JULIAN DATES
    // =========================================================================

    // Generic tradition aliases kept for backward compatibility
    public const JD_PHUGPA = 2359237.0; // legacy alias
    public const JD_TSURPHU = 2353745.0; // legacy alias
    public const JD_MONGOLIAN = 2359237.0; // legacy alias
    public const JD_BHUTAN = 2361807.0; // legacy alias

    // Named per tradition + epoch year
    public const JD_KARANA_E806 = 2015531.0;
    public const JD_PHUGPA_E1927 = 2424972.0;
    public const JD_PHUGPA_E1987 = 2446914.0;
    public const JD_BHUTAN_E1754 = 2361807.0;
    public const JD_TSURPHU_E1732 = 2353745.0;
    public const JD_TSURPHU_E1852 = 2397598.0;
    public const JD_MONGOL_E1747 = 2359237.0;

    // =========================================================================
    // §4  TRADITIONAL TIBETAN MEAN-MOTION INCREMENTS (Grub-rtsis / Siddhānta)
    //
    //  All four principal traditions (Phugpa, Tsurphu, Bhutan, Mongol) share
    //  these increments; they differ only in epoch offsets (§6).
    // =========================================================================

    /** Mean synodic month m₁ = 167025/5656 ≈ 29.530 587 days */
    public const TRAD_M1_NUM = 167025;
    public const TRAD_M1_DEN = 5656;
    public const TRAD_MEAN_SYNODIC_MONTH = 167025 / 5656;  // ≈ 29.53058699

    /** Mean lunar-day length m₂ = 11135/11312 = m₁/30 ≈ 0.984 353 days */
    public const TRAD_M2_NUM = 11135;
    public const TRAD_M2_DEN = 11312;
    public const TRAD_MEAN_LUNAR_DAY = 11135 / 11312;  // ≈ 0.98435290

    /** Mean solar advance per lunation s₁ = 65/804 turns ≈ 0.080 846 */
    public const TRAD_S1_NUM = 65;
    public const TRAD_S1_DEN = 804;
    public const TRAD_SOLAR_ADVANCE_PER_LUNATION = 65 / 804;       // ≈ 0.08084577

    /** Mean solar advance per lunar-day s₂ = 13/4824 = s₁/30 turns */
    public const TRAD_S2_NUM = 13;
    public const TRAD_S2_DEN = 4824;
    public const TRAD_SOLAR_ADVANCE_PER_DAY = 13 / 4824;      // ≈ 0.00269486

    /** Mean lunar anomaly per lunation a₁ = 253/3528 turns ≈ 0.071 712 */
    public const TRAD_A1_NUM = 253;
    public const TRAD_A1_DEN = 3528;
    public const TRAD_LUNAR_ANOMALY_PER_LUNATION = 253 / 3528;     // ≈ 0.07171202

    /**
     * Mean lunar anomaly per lunar-day a₂ = 1/28 (standard)
     * Alternative (Minling Lochen): a₂ = (1 + a₁)/30 = 3781/105840
     */
    public const TRAD_A2_NUM = 1;
    public const TRAD_A2_DEN = 28;
    public const TRAD_LUNAR_ANOMALY_PER_DAY = 1 / 28;         // ≈ 0.03571429
    public const TRAD_A2_MINLING_NUM = 3781;
    public const TRAD_A2_MINLING_DEN = 105840;
    public const TRAD_LUNAR_ANOMALY_PER_DAY_MINLING = 3781 / 105840;

    // =========================================================================
    // §5  KARANA (byed-rtsis) MEAN-MOTION INCREMENTS
    // =========================================================================

    /** Karana mean synodic month m₁ = 10631/360 ≈ 29.530 556 days */
    public const KARANA_M1_NUM = 10631;
    public const KARANA_M1_DEN = 360;
    public const KARANA_MEAN_SYNODIC_MONTH = 10631 / 360;    // ≈ 29.53055556

    /** Karana solar advance per lunation s₁ = 1277/15795 turns */
    public const KARANA_S1_NUM = 1277;
    public const KARANA_S1_DEN = 15795;
    public const KARANA_SOLAR_ADVANCE_PER_LUNATION = 1277 / 15795;   // ≈ 0.08084837

    // =========================================================================
    // §6  TIBETAN INTERCALATION AXIOM  (67 lunar months = 65 solar months)
    // =========================================================================

    /** P in the P/Q intercalation ratio (solar months) */
    public const TRAD_INTERCALATION_P = 65;
    /** Q in the P/Q intercalation ratio (lunar months) */
    public const TRAD_INTERCALATION_Q = 67;
    /** Leap months per Q-lunation cycle: ℓ = Q − P */
    public const TRAD_LEAP_MONTHS_PER_CYCLE = 2;
    /** Total lunations in the 65-year (804-lunation) cycle */
    public const TRAD_LUNATIONS_PER_65_YEAR_CYCLE = 804;
    /** Derived mean solar year: Y_model = (804/65)·m₁ ≈ 365.271 days */
    public const TRAD_MEAN_SOLAR_YEAR_DAYS = 365.270645;
    /** Seasonal drift: ≈ +2.85 days per century (positive = drifts later) */
    public const TRAD_SEASONAL_DRIFT_DAYS_PER_CENT = 2.85;

    // =========================================================================
    // §7  INTERCALATION PARAMETERS PER TRADITION
    //     β* = congruence shift;  τ = lower bound of trigger set {τ, τ+1}
    //     Leap rule: I = (2·M* + β*) mod 65 ∈ {τ, τ+1}
    // =========================================================================

    public const INTERCALATION_BETA_KARANA_E806 = 0;
    public const INTERCALATION_TAU_KARANA_E806 = 63;  // trigger {63, 64}

    public const INTERCALATION_BETA_PHUGPA_E1927 = 55;
    public const INTERCALATION_TAU_PHUGPA_E1927 = 48;  // trigger {48, 49}

    public const INTERCALATION_BETA_PHUGPA_E1987 = 0;
    public const INTERCALATION_TAU_PHUGPA_E1987 = 48;  // trigger {48, 49}

    /**
     * Bhutan uses the reparametrized repeat-label convention:
     * trigger {57, 58} (shifted from the raw {59, 60} by the Remark 2.4 offset)
     */
    public const INTERCALATION_BETA_BHUTAN_E1754 = 2;
    public const INTERCALATION_TAU_BHUTAN_E1754 = 57;  // trigger {57, 58}

    public const INTERCALATION_BETA_TSURPHU_E1732 = 59;
    public const INTERCALATION_TAU_TSURPHU_E1732 = 0;   // trigger {0, 1}

    public const INTERCALATION_BETA_TSURPHU_E1852 = 14;
    public const INTERCALATION_TAU_TSURPHU_E1852 = 0;   // trigger {0, 1}

    public const INTERCALATION_BETA_MONGOL_E1747 = 10;
    public const INTERCALATION_TAU_MONGOL_E1747 = 46;  // trigger {46, 47}

    // =========================================================================
    // §8  EPOCH OFFSETS  (fractional part of m₀, s₀ mod 1, a₀ mod 1)
    //     m₀ = JD_epoch + M0_FRAC_*
    //     s₀ and a₀ are in turns, taken mod 1
    // =========================================================================

    // — m₀ fractional day offsets —
    public const M0_FRAC_KARANA_E806_NUM = 1;
    public const M0_FRAC_KARANA_E806_DEN = 2;
    public const M0_FRAC_KARANA_E806 = 1 / 2;            // 0.5

    public const M0_FRAC_PHUGPA_E1927_NUM = 5457;
    public const M0_FRAC_PHUGPA_E1927_DEN = 5656;
    public const M0_FRAC_PHUGPA_E1927 = 5457 / 5656;

    public const M0_FRAC_PHUGPA_E1987_NUM = 135;
    public const M0_FRAC_PHUGPA_E1987_DEN = 707;
    public const M0_FRAC_PHUGPA_E1987 = 135 / 707;

    public const M0_FRAC_BHUTAN_E1754_NUM = 52;
    public const M0_FRAC_BHUTAN_E1754_DEN = 707;
    public const M0_FRAC_BHUTAN_E1754 = 52 / 707;

    public const M0_FRAC_TSURPHU_E1732_NUM = 1795153;
    public const M0_FRAC_TSURPHU_E1732_DEN = 7635600;
    public const M0_FRAC_TSURPHU_E1732 = 1795153 / 7635600;

    public const M0_FRAC_TSURPHU_E1852_NUM = 1197103;
    public const M0_FRAC_TSURPHU_E1852_DEN = 7635600;
    public const M0_FRAC_TSURPHU_E1852 = 1197103 / 7635600;

    public const M0_FRAC_MONGOL_E1747_NUM = 2603;
    public const M0_FRAC_MONGOL_E1747_DEN = 2828;
    public const M0_FRAC_MONGOL_E1747 = 2603 / 2828;

    // — Mean solar longitudes s₀ at epoch (turns, mod 1) —
    public const S0_KARANA_E806_NUM = 809;
    public const S0_KARANA_E806_DEN = 810;
    public const S0_KARANA_E806 = 809 / 810;

    public const S0_PHUGPA_E1927_NUM = 749;
    public const S0_PHUGPA_E1927_DEN = 804;
    public const S0_PHUGPA_E1927 = 749 / 804;

    public const S0_PHUGPA_E1987 = 0.0;

    public const S0_BHUTAN_E1754_NUM = 1;
    public const S0_BHUTAN_E1754_DEN = 67;
    public const S0_BHUTAN_E1754 = 1 / 67;

    public const S0_TSURPHU_E1732_NUM = -5983;
    public const S0_TSURPHU_E1732_DEN = 108540;
    public const S0_TSURPHU_E1732 = -5983 / 108540;

    public const S0_TSURPHU_E1852_NUM = 23;
    public const S0_TSURPHU_E1852_DEN = 27135;
    public const S0_TSURPHU_E1852 = 23 / 27135;

    public const S0_MONGOL_E1747_NUM = 397;
    public const S0_MONGOL_E1747_DEN = 402;
    public const S0_MONGOL_E1747 = 397 / 402;

    // — Mean lunar anomaly a₀ at epoch (turns, mod 1) —
    public const A0_KARANA_E806_NUM = 53;
    public const A0_KARANA_E806_DEN = 252;
    public const A0_KARANA_E806 = 53 / 252;

    public const A0_PHUGPA_E1927_NUM = 1741;
    public const A0_PHUGPA_E1927_DEN = 3528;
    public const A0_PHUGPA_E1927 = 1741 / 3528;

    public const A0_PHUGPA_E1987_NUM = 38;
    public const A0_PHUGPA_E1987_DEN = 49;
    public const A0_PHUGPA_E1987 = 38 / 49;

    public const A0_BHUTAN_E1754_NUM = 17;
    public const A0_BHUTAN_E1754_DEN = 147;
    public const A0_BHUTAN_E1754 = 17 / 147;

    public const A0_TSURPHU_E1732_NUM = 207;
    public const A0_TSURPHU_E1732_DEN = 392;
    public const A0_TSURPHU_E1732 = 207 / 392;

    public const A0_TSURPHU_E1852_NUM = 1;
    public const A0_TSURPHU_E1852_DEN = 49;
    public const A0_TSURPHU_E1852 = 1 / 49;

    public const A0_MONGOL_E1747_NUM = 1523;
    public const A0_MONGOL_E1747_DEN = 1764;
    public const A0_MONGOL_E1747 = 1523 / 1764;

    // =========================================================================
    // §9  EPOCH CONSTANTS AT JD 2015531 (23 March 806 AD)
    //     Normalised values from Janson [3], Table 6.
    //     m₀ = time offset (frac. days), s₀ = mean solar longitude (turns),
    //     a₀ = mean lunar anomaly (turns)
    // =========================================================================

    public const EPOCH806_JD = 2015531.0;
    public const EPOCH806_M0_PHUGPA = 2.376238;
    public const EPOCH806_S0_PHUGPA = 0.004975;
    public const EPOCH806_A0_PHUGPA = 0.206349;
    public const EPOCH806_M0_TSURPHU = 2.422338;
    public const EPOCH806_S0_TSURPHU = 0.018261;
    public const EPOCH806_A0_TSURPHU = 0.210317;
    public const EPOCH806_M0_BHUTAN = 2.410537;
    public const EPOCH806_S0_BHUTAN = 0.017413;
    public const EPOCH806_A0_BHUTAN = 0.220522;
    public const EPOCH806_M0_MONGOL = 2.418494;
    public const EPOCH806_S0_MONGOL = 0.023632;
    public const EPOCH806_A0_MONGOL = 0.207200;

    // =========================================================================
    // §10  TIE-CASE PERIODICITY CONSTANTS  (§3.3.3 of the paper)
    // =========================================================================

    /** den(m₁) = 5656 = 2³·7·101  →  period of mean-date fractional part */
    public const TIE_PERIOD_MEAN_DATE = 5656;
    /** den(s₁) = 804 = 2²·3·67  →  period of solar-table argument */
    public const TIE_PERIOD_SOLAR = 804;
    /** den(a₁) = 3528 = 2³·3²·7²  →  period of lunar-table argument */
    public const TIE_PERIOD_LUNAR = 3528;
    /** lcm(804, 3528) = 236 376  →  period of combined correction */
    public const TIE_PERIOD_CORRECTION = 236376;
    /** lcm(5656, 236376) = 23 873 976 ≈ 1.9 million years in lunations */
    public const TIE_PERIOD_FULL = 23873976;
    /** gcd(5656, 236376) = 56  (used in CRT prefilter) */
    public const TIE_GCD_MD_CORR = 56;
    /** CRT prefilter modulus: 67·101 = 6767 */
    public const TIE_CRT_MODULUS = 6767;

    // Tie-case 101-filter: n ≡ n₀ + 37·d  (mod 101)
    public const TIE_101_SLOPE = 37;
    public const TIE_101_INTERCEPT_PHUGPA = 25;
    public const TIE_101_INTERCEPT_TSURPHU_E1732 = 67;
    public const TIE_101_INTERCEPT_TSURPHU_E1852 = 97;
    public const TIE_101_INTERCEPT_BHUTAN = 84;
    public const TIE_101_INTERCEPT_MONGOL = 82;

    // Tie-case 67-filter: n ≡ ν₀ + 29·d  (mod 67)
    public const TIE_67_SLOPE = 29;
    public const TIE_67_INTERCEPT_PHUGPA = 21;
    public const TIE_67_INTERCEPT_TSURPHU_E1732 = 10;
    public const TIE_67_INTERCEPT_TSURPHU_E1852 = 46;
    public const TIE_67_INTERCEPT_BHUTAN = 6;
    public const TIE_67_INTERCEPT_MONGOL = 62;

    // =========================================================================
    // §11  REFORM ARITHMETIC CYCLES  (§4.1.1 of the paper)
    // =========================================================================

    // ── 334-year cycle: most practical arithmetic reform ──────────────────
    /** P (solar months) in the 334-year P/Q ratio */
    public const REFORM_334_P = 1336;
    /** Q (lunations) in the 334-year P/Q ratio */
    public const REFORM_334_Q = 1377;
    /** Total lunations in 334 years */
    public const REFORM_334_LUNATIONS = 4131;
    /** Leap months per 334-year cycle: Q − P */
    public const REFORM_334_LEAP_MONTHS = 123;
    /** Mean year length ≈ 365.242 104 days  (drift ≈ −2 h/millennium) */
    public const REFORM_334_MEAN_YEAR_DAYS = 365.242104;
    /** Seasonal drift: ≈ −7.4 s/year = −2.06 h/millennium */
    public const REFORM_334_DRIFT_SEC_PER_YEAR = -7.4;

    // ── 353-year cycle ────────────────────────────────────────────────────
    public const REFORM_353_P = 4236; // 12·353
    public const REFORM_353_Q = 4366;
    public const REFORM_353_LEAP_MONTHS = 130;
    public const REFORM_353_MEAN_YEAR_DAYS = 365.242354;
    public const REFORM_353_DRIFT_SEC_PER_YEAR = 14.2;

    // ── 687-year cycle ────────────────────────────────────────────────────
    public const REFORM_687_P = 8244; // 12·687
    public const REFORM_687_Q = 8497;
    public const REFORM_687_LEAP_MONTHS = 253;
    public const REFORM_687_MEAN_YEAR_DAYS = 365.242233;
    public const REFORM_687_DRIFT_SEC_PER_YEAR = 3.7;

    // ── 1021-year cycle (near-zero drift) ────────────────────────────────
    public const REFORM_1021_P = 12252; // 12·1021
    public const REFORM_1021_Q = 12628;
    public const REFORM_1021_LEAP_MONTHS = 376;
    public const REFORM_1021_MEAN_YEAR_DAYS = 365.242190;
    public const REFORM_1021_DRIFT_SEC_PER_YEAR = 0.03;

    // ── Metonic cycle (19-year, for reference) ────────────────────────────
    public const METONIC_P = 228;  // 12·19
    public const METONIC_Q = 235;
    public const METONIC_LEAP_MONTHS = 7;
    public const METONIC_DRIFT_DAYS_PER_19_YEARS = 0.0868;
    public const METONIC_DRIFT_DAYS_PER_CENTURY = 0.457;

    // ── 168/163-year cycle (for reference) ───────────────────────────────
    public const REFORM_163_P = 1956; // 12·163
    public const REFORM_163_Q = 2016; // actually Q=168 lunations per period
    public const REFORM_163_LEAP_MONTHS = 5;
    public const REFORM_163_DRIFT_DAYS_PER_163_YEARS = -0.810;
    public const REFORM_163_DRIFT_DAYS_PER_CENTURY = -0.497;

    // ── First definition-point anchor for tropical reforms ────────────────
    /** d₁ = 336° tropical  (24° before vernal equinox, 66° past winter solstice) */
    public const REFORM_DEFINITION_POINT_D1_DEG = 336.0;

    // =========================================================================
    // §12  REFORM MEAN-MOTION CONSTANTS
    // =========================================================================

    // Mean synodic month candidates (continued-fraction convergents of S_syn)
    /** Traditional m₁ = 167025/5656 (same as TRAD_MEAN_SYNODIC_MONTH) */
    public const CF_M1_TRADITIONAL = 167025 / 5656;
    /** Low-denominator CF upgrade: 51649/1749 ≈ 29.530 588 (error +0.005 s/lun) */
    public const CF_M1_LOW_DEN_NUM = 51649;
    public const CF_M1_LOW_DEN_DEN = 1749;
    public const CF_M1_LOW_DEN = 51649 / 1749;
    /** High-accuracy CF: 283346/9595 (error −0.0004 s/lun; used in reform) */
    public const CF_M1_HIGH_NUM = 283346;
    public const CF_M1_HIGH_DEN = 9595;
    public const CF_M1_HIGH = 283346 / 9595;
    /** Near-convergent: 2756710/93351 (error −0.00001 s/lun) */
    public const CF_M1_NEAR_NUM = 2756710;
    public const CF_M1_NEAR_DEN = 93351;
    public const CF_M1_NEAR = 2756710 / 93351;

    /** Reform solar advance per lunation: s₁ = 334/4131 turns */
    public const REFORM_S1_NUM = 334;
    public const REFORM_S1_DEN = 4131;
    public const REFORM_SOLAR_ADVANCE_PER_LUNATION = 334 / 4131;

    /** Reform solar advance per lunar-day: s₂ = 167/61965 turns */
    public const REFORM_S2_NUM = 167;
    public const REFORM_S2_DEN = 61965;
    public const REFORM_SOLAR_ADVANCE_PER_DAY = 167 / 61965;

    // Lunar anomaly rate candidates
    /** Traditional a₁ = 253/3528 (drift −7.0°/1000y) */
    public const CF_A1_TRADITIONAL = 253 / 3528;
    /** Low-denom CF a₁ = 18/251 (drift −2.0°/1000y) */
    public const CF_A1_LOW_NUM = 18;
    public const CF_A1_LOW_DEN = 251;
    public const CF_A1_LOW = 18 / 251;
    /** High-accuracy CF a₁ = 503/7014 (drift +0.57°/1000y) */
    public const CF_A1_HIGH_NUM = 503;
    public const CF_A1_HIGH_DEN = 7014;
    public const CF_A1_HIGH = 503 / 7014;
    /** Optimal a₁ = 4583/63907 (drift −0.02°/1000y; recommended for reform) */
    public const REFORM_A1_NUM = 4583;
    public const REFORM_A1_DEN = 63907;
    public const REFORM_LUNAR_ANOMALY_PER_LUNATION = 4583 / 63907;

    // Solar anomaly rate candidates
    /** Traditional (tied to mean Sun): s₁ = 65/804 (same as solar advance) */
    public const CF_R1_TRADITIONAL = 65 / 804;
    /** Low-denom CF r₁ = 122/1509 (drift +0.26°/1000y) */
    public const CF_R1_LOW_NUM = 122;
    public const CF_R1_LOW_DEN = 1509;
    public const CF_R1_LOW = 122 / 1509;
    /** High-accuracy CF r₁ = 1689/20891 (drift +0.12°/1000y; recommended) */
    public const REFORM_R1_NUM = 1689;
    public const REFORM_R1_DEN = 20891;
    public const REFORM_SOLAR_ANOMALY_PER_LUNATION = 1689 / 20891;

    // Lunar latitude (draconic) rate candidates
    /** Low-denom CF f₁ = 61/716 (drift −1.4°/1000y) */
    public const CF_F1_LOW_NUM = 61;
    public const CF_F1_LOW_DEN = 716;
    public const CF_F1_LOW = 61 / 716;
    /** Optimal f₁ = 324/3803 (drift +0.24°/1000y; recommended) */
    public const REFORM_F1_NUM = 324;
    public const REFORM_F1_DEN = 3803;
    public const REFORM_LUNAR_LATITUDE_PER_LUNATION = 324 / 3803;

    // =========================================================================
    // §13  REFORM EPOCH CONSTANTS — E1987
    //     Arcsecond grid denominator: 1 296 000 = 2⁷·3⁴·5³
    // =========================================================================

    /** Universal arcsecond denominator: 360°·60'·60" */
    public const ARCSECONDS_PER_REVOLUTION = 1296000;

    /** m₀ exact Julian Date (TT): 244691379521131/100000000 */
    public const REFORM_E1987_M0_EXACT = 2446913.79521131;
    public const REFORM_E1987_M0_EXACT_NUM = 244691379521131;
    public const REFORM_E1987_M0_EXACT_DEN = 100000000;

    /** m₀ optimised (small prime denominator): 160957989449/65780 */
    public const REFORM_E1987_M0_OPT_NUM = 160957989449;
    public const REFORM_E1987_M0_OPT_DEN = 65780;

    /** s₀ (mean solar longitude at epoch): 128634/1296000 turns */
    public const REFORM_E1987_S0_ARCSEC = 128634;

    /** a₀ (lunar anomaly at epoch): 389900/1296000 turns */
    public const REFORM_E1987_A0_ARCSEC = 389900;

    /** r₀ (solar anomaly at epoch): 406845/1296000 turns = 9041/28800 */
    public const REFORM_E1987_R0_ARCSEC = 406845;
    public const REFORM_E1987_R0_OPT_NUM = 9041;
    public const REFORM_E1987_R0_OPT_DEN = 28800;

    /** f₀ (lunar latitude argument at epoch): 91591/1296000 turns */
    public const REFORM_E1987_F0_ARCSEC = 91591;
    /** f₀ optimised: 4596/65033 */
    public const REFORM_E1987_F0_OPT_NUM = 4596;
    public const REFORM_E1987_F0_OPT_DEN = 65033;

    // =========================================================================
    // §14  MODERN MEAN PERIODS AT J2000.0
    // =========================================================================

    // — Legacy alias (kept for backward compatibility) —
    public const MEAN_SYNODIC_MONTH = 29.530588861;
    public const MEAN_SYNODIC_MONTH_2 = 29.530588853;
    public const MEAN_DRACONITIC_MONTH = 27.212220815;
    public const MEAN_TROPICAL_MONTH = 27.321582252;
    public const MEAN_SIDEREAL_MONTH = 27.321661554;
    public const SYNODIC_MONTHS_PER_CENTURY = 1236.85;
    public const SIDEREAL = 365.25636574;

    /** Mean tropical year (days) */
    public const JD_UNIX_EPOCH = 2440587.5;
    public const J2000_UNIX = 946728000.0;
    public const MEAN_OBLIQUITY_J2000_DEG = 23.43928; // mean obliquity at J2000, degrees
    public const MEAN_TROPICAL_YEAR = 365.2421897;
    /** Mean anomalistic year (perihelion-to-perihelion, days) */
    public const MEAN_ANOMALISTIC_YEAR = 365.2596359;
    /** Mean synodic month (new moon to new moon, days) — J2000.0 precision */
    public const MEAN_SYNODIC_MONTH_J2000 = 29.53058885;
    /** Mean anomalistic month (perigee to perigee, days) */
    public const MEAN_ANOMALISTIC_MONTH = 27.554549886;
    /** Mean draconic month (node to node, days) — approximate */
    public const MEAN_DRACONIC_MONTH = 27.21222082;

    // Secular trends (polynomial coefficients in days; T = Julian centuries from J2000)
    public const TROPICAL_YEAR_SECULAR_T1 = -6.15e-6;
    public const TROPICAL_YEAR_SECULAR_T2 = -7.29e-10;
    public const SYNODIC_MONTH_SECULAR_T1 = 2.16e-7;
    public const SYNODIC_MONTH_SECULAR_T2 = -3.64e-10;
    public const ANOMALISTIC_YEAR_SECULAR_T1 = 3.04e-6;
    public const ANOMALISTIC_MONTH_SECULAR_T1 = -1.039e-5;
    public const ANOMALISTIC_MONTH_SECULAR_T2 = -2.0e-9;

    // =========================================================================
    // §15  J2000.0 FIRST-ANOMALY MODEL PARAMETERS  (Table 2 in paper)
    // =========================================================================

    // Sun
    /** Mean longitude λ̄⊙ at J2000.0 (degrees) */
    public const SUN_MEAN_LONGITUDE_J2000_DEG = 280.46645;
    /** Mean angular velocity ω⊙ (degrees/day) */
    public const SUN_MEAN_MOTION_DEG_PER_DAY = 0.9856473602;
    /** Mean anomaly A⊙ at J2000.0 (degrees) */
    public const SUN_ANOMALY_J2000_DEG = 357.5291092;
    /** Anomaly rate Ω⊙ (degrees/day) */
    public const SUN_ANOMALY_RATE_DEG_PER_DAY = 0.9856002800;
    /** First-harmonic equation-of-center amplitude ε⊙ (degrees) */
    public const SUN_EQUATION_CENTER_AMP_DEG = 1.915;

    // Moon
    /** Mean longitude λ̄☾ at J2000.0 (degrees) */
    public const MOON_MEAN_LONGITUDE_J2000_DEG = 218.3164477;
    /** Mean angular velocity ω☾ (degrees/day) */
    public const MOON_MEAN_MOTION_DEG_PER_DAY = 13.1763965268;
    /** Mean anomaly A☾ at J2000.0 (degrees) */
    public const MOON_ANOMALY_J2000_DEG = 134.9633964;
    /** Anomaly rate Ω☾ (degrees/day) */
    public const MOON_ANOMALY_RATE_DEG_PER_DAY = 13.0649929509;
    /** First-harmonic equation-of-center amplitude ε☾ (degrees) */
    public const MOON_EQUATION_CENTER_AMP_DEG = 6.29;
    /** Period of distance oscillation */
    public const MOON_DISTANCE_PERIOD = 27.55454988;
    /** Reference cycle offset in days. */
    public const MOON_SYNODIC_OFFSET = 2451550.26;
    /** Period of moon cycle in days. */
    public const MOON_SYNODIC_PERIOD = 29.530588853;
    public const MOON_DISTANCE_OFFSET = 2451562.2;
    public const KNOWN_NEW_MOON_JD = 2451550.1; // 6 Jan 2000 18:14 UTC
    // =========================================================================
    // §16  DERIVED INVERSE-MODEL CONSTANTS  (Table 3 in paper, J2000.0)
    //     Used in the closed-form inverse elongation approximation (§3.3.1)
    // =========================================================================

    /** Synodic elongation rate ω = ω☾ − ω⊙ (degrees/day) */
    public const SYNODIC_ELONGATION_RATE_DEG_PER_DAY = 12.1907491666;
    /** m̂₁ = 1/ω (days per full-turn elongation cycle) */
    public const INVERSE_MODEL_M1 = 29.5305887304;
    /** m̂₀ = −Ē(0)/ω (days) */
    public const INVERSE_MODEL_M0 = 5.0981282153;
    /** b̂_moon = ε_moon/ω (days) */
    public const INVERSE_MODEL_B_MOON = 0.5159650087;
    /** b̂_sun = ε_sun/ω (days) */
    public const INVERSE_MODEL_B_SUN = 0.1570863262;
    /** â₁ = Ω_moon/ω (dimensionless anomaly slope, moon) */
    public const INVERSE_MODEL_A1_MOON = 1.0717137044;
    /** r̂₁ = Ω_sun/ω (dimensionless anomaly slope, sun) */
    public const INVERSE_MODEL_R1_SUN = 0.0808482126;
    /** â₀ (moon anomaly phase at reference elongation, degrees) */
    public const INVERSE_MODEL_A0_MOON_DEG = 201.5704056;
    /** r̂₀ (sun anomaly phase at reference elongation, degrees) */
    public const INVERSE_MODEL_R0_SUN_DEG = 2.5538258;

    // =========================================================================
    // §17  SOLAR LONGITUDE MODEL  (Meeus / VSOP87 truncation, §D.5)
    // =========================================================================

    // L₀ = mean longitude of the Sun
    public const SOLAR_L0_J2000_DEG = 280.46646;
    public const SOLAR_L0_RATE_DEG_PER_CY = 36000.76983;
    public const SOLAR_L0_RATE2_DEG_PER_CY2 = 0.0003032;

    // M = mean anomaly of the Sun
    public const SOLAR_M_J2000_DEG = 357.52911;
    public const SOLAR_M_RATE_DEG_PER_CY = 35999.05029;
    public const SOLAR_M_RATE2_DEG_PER_CY2 = -0.0001537;

    // Equation of Center: C_sun = C1·sin M + C2·sin 2M + C3·sin 3M
    public const SOLAR_EQC_C1_0 = 1.914602;
    public const SOLAR_EQC_C1_T = -0.004817;
    public const SOLAR_EQC_C1_T2 = -0.000014;
    public const SOLAR_EQC_C2_0 = 0.019993;
    public const SOLAR_EQC_C2_T = -0.000101;
    public const SOLAR_EQC_C3 = 0.000289;

    // Apparent longitude correction
    /** Aberration constant (degrees) */
    public const SOLAR_ABERRATION_DEG = -0.00569;
    /** Nutation amplitude (degrees) */
    public const SOLAR_NUTATION_AMP_DEG = -0.00478;
    /** Longitude of Moon's ascending node Ω at J2000.0 (degrees) */
    public const LUNAR_NODE_OMEGA_J2000_DEG = 125.04;
    /** Ω secular rate (degrees/century) */
    public const LUNAR_NODE_OMEGA_RATE_DEG_PER_CY = -1934.136;

    // =========================================================================
    // §19  MEAN NEW MOON FORMULA  (Meeus §D.8)
    //     JD_E_mean = BASE + SYNODIC_COEFF·k + T2_COEFF·T²
    //     where k = lunation index (0 = first new moon of 2000), T ≈ k/1236.85
    // =========================================================================

    public const BASE_JULIAN_EPHEMERIS_DATE = 2451550.09765; // legacy
    public const MEAN_NEW_MOON_BASE_JD = 2451550.09766;
    public const MEAN_NEW_MOON_SYNODIC_COEFF = 29.530588861;
    public const MEAN_NEW_MOON_T2_COEFF = 0.0001337;
    public const MEAN_SIDEREAL_YEAR = 365.25636;

    // =========================================================================
    // GMST AND SIDEREAL ROTATION
    // =========================================================================

    public const GMST_J2000_DEG = 280.46061837;
    public const GMST_DEG_PER_DAY = 360.98564736629;
    public const GMST_CENTURY3_DENOM = 38710000.0;

    // =========================================================================
    // SUNRISE / CIVIL-DAY TRIGGER CONSTANTS
    // =========================================================================

    /** Refraction + solar semidiameter combined depression threshold: −0.833° */
    public const SUNRISE_DEPRESSION_DEG = -0.833;
    /** Standard atmospheric refraction at the horizon (arcminutes) */
    public const SUNRISE_REFRACTION_ARCMIN = 34.0;
    /** Solar semidiameter (arcminutes) */
    public const SOLAR_SEMIDIAMETER_ARCMIN = 16.0;
    /**
     * 5:56 AM proxy as day fraction (89/360):
     * accounts for ~4 min advance due to refraction + semidiameter
     */
    public const SUNRISE_PROXY_5H56_DAY_FRACTION = 89 / 360;
    /** 6:00 AM as day fraction (1/4) */
    public const SUNRISE_6AM_DAY_FRACTION = 1 / 4;

    /** Obliquity of ecliptic, J2000.0 approximate constant (degrees) */
    public const OBLIQUITY_J2000_DEG = 23.44;
    /** Obliquity precise value: 23°26'21.448" in degrees */
    public const OBLIQUITY_J2000_PRECISE_DEG = 23.4392911;
    /** Secular obliquity rate (arcseconds per century) */
    public const OBLIQUITY_SECULAR_RATE_ARCSEC_PER_CY = -46.8150;

    /** Rational sunrise depression: −1/432 turns = −50 arcminutes */
    public const SUNRISE_DEPRESSION_TURNS_NUM = -1;
    public const SUNRISE_DEPRESSION_TURNS_DEN = 432;
    public const SUNRISE_DEPRESSION_TURNS = -1 / 432;
    /** Rational obliquity: 4219/64800 turns ≈ 23°26'20" */
    public const OBLIQUITY_RATIONAL_NUM = 4219;
    public const OBLIQUITY_RATIONAL_DEN = 64800;
    public const OBLIQUITY_RATIONAL_TURNS = 4219 / 64800;

    // =========================================================================
    // GEOGRAPHIC COORDINATES  (principal tradition sites)
    // =========================================================================

    public const LHASA_LATITUDE_DEG = 29.65;
    public const LHASA_LONGITUDE_DEG = 91.10;
    public const THIMPHU_LATITUDE_DEG = 27.47;
    public const THIMPHU_LONGITUDE_DEG = 89.64;
    public const ULAANBAATAR_LATITUDE_DEG = 47.92;
    public const ULAANBAATAR_LONGITUDE_DEG = 106.92;

    // =========================================================================
    // LUNAR PERTURBATION AMPLITUDES  (degrees)
    // =========================================================================

    /** Evection: E·sin(2D − M') — largest D-coupled term */
    public const LUNAR_EVECTION_AMP_DEG = 1.274;
    /** Variation: V·sin(2D) — second largest D-coupled term */
    public const LUNAR_VARIATION_AMP_DEG = 0.66;
    /** Annual equation: ~0.186°·sin(M) — solar eccentricity modulation */
    public const LUNAR_ANNUAL_EQ_AMP_DEG = 0.186;
    /** Second elliptic term: ~0.214°·sin(2M') */
    public const LUNAR_SECOND_ELLIPTIC_AMP_DEG = 0.214;
    /** Reduction to ecliptic: ~0.114°·sin(2(L−F)) */
    public const LUNAR_REDUCTION_ECLIPTIC_AMP_DEG = 0.114;

    // Timing errors if each term is omitted (hours)
    public const LUNAR_EVECTION_TIMING_ERROR_HOURS = 2.6;
    public const LUNAR_VARIATION_TIMING_ERROR_HOURS = 1.4;
    public const LUNAR_ANNUAL_EQ_TIMING_MIN = 22;   // minutes
    public const LUNAR_SECOND_ELLIPTIC_TIMING_MIN = 25;   // minutes
    public const LUNAR_REDUCTION_TIMING_MIN = 13;   // minutes

    // =========================================================================
    // §24  ARCSECOND-GRID AMPLITUDES FOR RATIONAL REFORM SERIES  (§F.1.2)
    //     All values in integer arcseconds on the 1 296 000 turn grid
    // =========================================================================

    /** Solar equation of center, 1-term (arcseconds) */
    public const REFORM_SOLAR_EQC_1TERM_ARCSEC = 6893;
    /** Solar 2nd elliptic term (arcseconds) */
    public const REFORM_SOLAR_2ND_ELLIPTIC_ARCSEC = 72;

    /** Lunar major equation of center (arcseconds) */
    public const REFORM_LUNAR_EQC_ARCSEC = 22640;
    /** Lunar evection (arcseconds) */
    public const REFORM_LUNAR_EVECTION_ARCSEC = 4586;
    /** Lunar variation (arcseconds) */
    public const REFORM_LUNAR_VARIATION_ARCSEC = 2370;
    /** Lunar 2nd elliptic term (arcseconds) */
    public const REFORM_LUNAR_2ND_ELLIPTIC_ARCSEC = 769;
    /** Lunar annual equation (arcseconds, negative) */
    public const REFORM_LUNAR_ANNUAL_EQ_ARCSEC = -666;
    /** Lunar reduction to ecliptic (arcseconds, negative) */
    public const REFORM_LUNAR_RED_ECLIPTIC_ARCSEC = -412;

    // =========================================================================
    // §25  L0 MEAN-ELONGATION MODEL  (skipped-day arithmetic, §C)
    //     Civil-day advance: U/V turns of elongation per dawn step
    //     κ = U − V = number of skips per V-day cycle
    // =========================================================================

    /** Grub-rtsis (siddhānta): U = 11312, V = 11135, κ = 177 */
    public const L0_U_GRUB = 11312;
    public const L0_V_GRUB = 11135;
    public const L0_KAPPA_GRUB = 177;

    /** Karana: U = 10800, V = 10631, κ = 169 */
    public const L0_U_KARANA = 10800;
    public const L0_V_KARANA = 10631;
    public const L0_KAPPA_KARANA = 169;

    // =========================================================================
    // §31  J2000.0 HARMONIC RATE DENOMINATORS  (§F.1.1)
    //     Base: 36525 days × 360° × 10⁵ = 487·2⁸·3³·5⁸ = 1 314 900 000 000
    // =========================================================================

    /** Universal harmonic denominator for J2000.0 rate constants */
    public const J2000_HARMONIC_DENOM = 1314900000000;

    /** Solar mean motion (turns/day): 3600076983 / 1314900000000 */
    public const J2000_SOLAR_MOTION_NUM = 3600076983;
    /** Lunar elongation rate (turns/day): 4452671114 / 131490000000 */
    public const J2000_ELONGATION_RATE_NUM = 4452671114;
    public const J2000_ELONGATION_RATE_DEN = 131490000000;
    /** Solar anomaly rate (turns/day): 3599905029 / 1314900000000 */
    public const J2000_SOLAR_ANOMALY_NUM = 3599905029;
    /** Lunar anomaly rate (turns/day): 47719886751 / 1314900000000 */
    public const J2000_LUNAR_ANOMALY_NUM = 47719886751;
    /** Lunar latitude rate (turns/day): 48320201752 / 1314900000000 */
    public const J2000_LUNAR_LATITUDE_NUM = 48320201752;
    public const MERCURY_SYNODIC_PERIOD = 115.8775;
    public const VENUS_SYNODIC_PERIOD = 583.9214;
    public const MARS_SYNODIC_PERIOD = 779.9361;
    public const JUPITER_SYNODIC_PERIOD = 398.8840;
    public const SATURN_SYNODIC_PERIOD = 378.0919;
    public const URANUS_SYNODIC_PERIOD = 378.0919;
    public const NEPTUNE_SYNODIC_PERIOD = 378.0919;
    public const PLUTO_SYNODIC_PERIOD = 366.7207;
}
