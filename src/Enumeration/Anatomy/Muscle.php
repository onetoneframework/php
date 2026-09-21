<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Enumeration\Anatomy;

use function in_array;

enum Muscle: string
{
    // -------------------------------------------------------------------------
    // Head & Face
    // -------------------------------------------------------------------------
    case FRONTALIS = 'frontalis';
    case ORBICULARIS_OCULI = 'orbicularis_oculi';
    case ORBICULARIS_ORIS = 'orbicularis_oris';
    case ZYGOMATICUS_MAJOR = 'zygomaticus_major';
    case ZYGOMATICUS_MINOR = 'zygomaticus_minor';
    case BUCCINATOR = 'buccinator';
    case MASSETER = 'masseter';
    case TEMPORALIS = 'temporalis';
    case MEDIAL_PTERYGOID = 'medial_pterygoid';
    case LATERAL_PTERYGOID = 'lateral_pterygoid';
    case LEVATOR_LABII_SUPERIORIS = 'levator_labii_superioris';
    case DEPRESSOR_LABII_INFERIORIS = 'depressor_labii_inferioris';
    case DEPRESSOR_ANGULI_ORIS = 'depressor_anguli_oris';
    case MENTALIS = 'mentalis';
    case NASALIS = 'nasalis';
    case CORRUGATOR_SUPERCILII = 'corrugator_supercilii';
    case PROCERUS = 'procerus';
    case RISORIUS = 'risorius';
    case PLATYSMA = 'platysma';

    // -------------------------------------------------------------------------
    // Neck
    // -------------------------------------------------------------------------
    case STERNOCLEIDOMASTOID = 'sternocleidomastoid';
    case ANTERIOR_SCALENE = 'anterior_scalene';
    case MIDDLE_SCALENE = 'middle_scalene';
    case POSTERIOR_SCALENE = 'posterior_scalene';
    case LONGUS_COLLI = 'longus_colli';
    case LONGUS_CAPITIS = 'longus_capitis';
    case RECTUS_CAPITIS_ANTERIOR = 'rectus_capitis_anterior';
    case RECTUS_CAPITIS_LATERALIS = 'rectus_capitis_lateralis';
    case SPLENIUS_CAPITIS = 'splenius_capitis';
    case SPLENIUS_CERVICIS = 'splenius_cervicis';
    case SEMISPINALIS_CAPITIS = 'semispinalis_capitis';
    case SUBOCCIPITAL_RECTUS_CAPITIS_POSTERIOR_MAJOR = 'suboccipital_rectus_capitis_posterior_major';
    case SUBOCCIPITAL_RECTUS_CAPITIS_POSTERIOR_MINOR = 'suboccipital_rectus_capitis_posterior_minor';
    case OBLIQUUS_CAPITIS_SUPERIOR = 'obliquus_capitis_superior';
    case OBLIQUUS_CAPITIS_INFERIOR = 'obliquus_capitis_inferior';
    case MYLOHYOID = 'mylohyoid';
    case GENIOHYOID = 'geniohyoid';
    case DIGASTRIC_ANTERIOR = 'digastric_anterior';
    case DIGASTRIC_POSTERIOR = 'digastric_posterior';
    case STYLOHYOID = 'stylohyoid';
    case OMOHYOID = 'omohyoid';
    case STERNOHYOID = 'sternohyoid';
    case STERNOTHYROID = 'sternothyroid';
    case THYROHYOID = 'thyrohyoid';

    // -------------------------------------------------------------------------
    // Thorax
    // -------------------------------------------------------------------------
    case PECTORALIS_MAJOR = 'pectoralis_major';
    case PECTORALIS_MINOR = 'pectoralis_minor';
    case SERRATUS_ANTERIOR = 'serratus_anterior';
    case SUBCLAVIUS = 'subclavius';
    case EXTERNAL_INTERCOSTAL = 'external_intercostal';
    case INTERNAL_INTERCOSTAL = 'internal_intercostal';
    case INNERMOST_INTERCOSTAL = 'innermost_intercostal';
    case TRANSVERSUS_THORACIS = 'transversus_thoracis';
    case LEVATORES_COSTARUM = 'levatores_costarum';
    case SUBCOSTALIS = 'subcostalis';
    case DIAPHRAGM = 'diaphragm';

    // -------------------------------------------------------------------------
    // Abdomen
    // -------------------------------------------------------------------------
    case RECTUS_ABDOMINIS = 'rectus_abdominis';
    case EXTERNAL_OBLIQUE = 'external_oblique';
    case INTERNAL_OBLIQUE = 'internal_oblique';
    case TRANSVERSUS_ABDOMINIS = 'transversus_abdominis';
    case PYRAMIDALIS = 'pyramidalis';
    case QUADRATUS_LUMBORUM = 'quadratus_lumborum';
    case PSOAS_MAJOR = 'psoas_major';
    case PSOAS_MINOR = 'psoas_minor';
    case ILIACUS = 'iliacus';
    case CREMASTER = 'cremaster';

    // -------------------------------------------------------------------------
    // Back & Spine
    // -------------------------------------------------------------------------
    case TRAPEZIUS_UPPER = 'trapezius_upper';
    case TRAPEZIUS_MIDDLE = 'trapezius_middle';
    case TRAPEZIUS_LOWER = 'trapezius_lower';
    case LATISSIMUS_DORSI = 'latissimus_dorsi';
    case RHOMBOID_MAJOR = 'rhomboid_major';
    case RHOMBOID_MINOR = 'rhomboid_minor';
    case LEVATOR_SCAPULAE = 'levator_scapulae';
    case SERRATUS_POSTERIOR_SUPERIOR = 'serratus_posterior_superior';
    case SERRATUS_POSTERIOR_INFERIOR = 'serratus_posterior_inferior';
    case ERECTOR_SPINAE_ILIOCOSTALIS = 'erector_spinae_iliocostalis';
    case ERECTOR_SPINAE_LONGISSIMUS = 'erector_spinae_longissimus';
    case ERECTOR_SPINAE_SPINALIS = 'erector_spinae_spinalis';
    case SEMISPINALIS_THORACIS = 'semispinalis_thoracis';
    case SEMISPINALIS_CERVICIS = 'semispinalis_cervicis';
    case MULTIFIDUS = 'multifidus';
    case ROTATORES = 'rotatores';
    case INTERSPINALES = 'interspinales';
    case INTERTRANSVERSARII = 'intertransversarii';

    // -------------------------------------------------------------------------
    // Shoulder
    // -------------------------------------------------------------------------
    case DELTOID_ANTERIOR = 'deltoid_anterior';
    case DELTOID_MIDDLE = 'deltoid_middle';
    case DELTOID_POSTERIOR = 'deltoid_posterior';
    case SUPRASPINATUS = 'supraspinatus';
    case INFRASPINATUS = 'infraspinatus';
    case TERES_MINOR = 'teres_minor';
    case TERES_MAJOR = 'teres_major';
    case SUBSCAPULARIS = 'subscapularis';
    case CORACOBRACHIALIS = 'coracobrachialis';

    // -------------------------------------------------------------------------
    // Upper Arm
    // -------------------------------------------------------------------------
    case BICEPS_BRACHII_LONG_HEAD = 'biceps_brachii_long_head';
    case BICEPS_BRACHII_SHORT_HEAD = 'biceps_brachii_short_head';
    case BRACHIALIS = 'brachialis';
    case TRICEPS_BRACHII_LONG_HEAD = 'triceps_brachii_long_head';
    case TRICEPS_BRACHII_MEDIAL_HEAD = 'triceps_brachii_medial_head';
    case TRICEPS_BRACHII_LATERAL_HEAD = 'triceps_brachii_lateral_head';
    case ANCONEUS = 'anconeus';

    // -------------------------------------------------------------------------
    // Forearm — Anterior
    // -------------------------------------------------------------------------
    case PRONATOR_TERES = 'pronator_teres';
    case FLEXOR_CARPI_RADIALIS = 'flexor_carpi_radialis';
    case PALMARIS_LONGUS = 'palmaris_longus';
    case FLEXOR_CARPI_ULNARIS = 'flexor_carpi_ulnaris';
    case FLEXOR_DIGITORUM_SUPERFICIALIS = 'flexor_digitorum_superficialis';
    case FLEXOR_DIGITORUM_PROFUNDUS = 'flexor_digitorum_profundus';
    case FLEXOR_POLLICIS_LONGUS = 'flexor_pollicis_longus';
    case PRONATOR_QUADRATUS = 'pronator_quadratus';

    // -------------------------------------------------------------------------
    // Forearm — Posterior
    // -------------------------------------------------------------------------
    case BRACHIORADIALIS = 'brachioradialis';
    case EXTENSOR_CARPI_RADIALIS_LONGUS = 'extensor_carpi_radialis_longus';
    case EXTENSOR_CARPI_RADIALIS_BREVIS = 'extensor_carpi_radialis_brevis';
    case EXTENSOR_DIGITORUM = 'extensor_digitorum';
    case EXTENSOR_DIGITI_MINIMI = 'extensor_digiti_minimi';
    case EXTENSOR_CARPI_ULNARIS = 'extensor_carpi_ulnaris';
    case SUPINATOR = 'supinator';
    case ABDUCTOR_POLLICIS_LONGUS = 'abductor_pollicis_longus';
    case EXTENSOR_POLLICIS_BREVIS = 'extensor_pollicis_brevis';
    case EXTENSOR_POLLICIS_LONGUS = 'extensor_pollicis_longus';
    case EXTENSOR_INDICIS = 'extensor_indicis';

    // -------------------------------------------------------------------------
    // Hand — Thenar & Hypothenar
    // -------------------------------------------------------------------------
    case ABDUCTOR_POLLICIS_BREVIS = 'abductor_pollicis_brevis';
    case FLEXOR_POLLICIS_BREVIS = 'flexor_pollicis_brevis';
    case OPPONENS_POLLICIS = 'opponens_pollicis';
    case ADDUCTOR_POLLICIS = 'adductor_pollicis';
    case ABDUCTOR_DIGITI_MINIMI_HAND = 'abductor_digiti_minimi_hand';
    case FLEXOR_DIGITI_MINIMI_BREVIS_HAND = 'flexor_digiti_minimi_brevis_hand';
    case OPPONENS_DIGITI_MINIMI = 'opponens_digiti_minimi';
    case PALMARIS_BREVIS = 'palmaris_brevis';
    case LUMBRICAL_HAND_FIRST = 'lumbrical_hand_first';
    case LUMBRICAL_HAND_SECOND = 'lumbrical_hand_second';
    case LUMBRICAL_HAND_THIRD = 'lumbrical_hand_third';
    case LUMBRICAL_HAND_FOURTH = 'lumbrical_hand_fourth';
    case DORSAL_INTEROSSEI_HAND = 'dorsal_interossei_hand';
    case PALMAR_INTEROSSEI_HAND = 'palmar_interossei_hand';

    // -------------------------------------------------------------------------
    // Pelvis & Perineum
    // -------------------------------------------------------------------------
    case LEVATOR_ANI_PUBOCOCCYGEUS = 'levator_ani_pubococcygeus';
    case LEVATOR_ANI_ILIOCOCCYGEUS = 'levator_ani_iliococcygeus';
    case LEVATOR_ANI_PUBORECTALIS = 'levator_ani_puborectalis';
    case COCCYGEUS = 'coccygeus';
    case OBTURATOR_INTERNUS = 'obturator_internus';
    case PIRIFORMIS = 'piriformis';
    case EXTERNAL_URETHRAL_SPHINCTER = 'external_urethral_sphincter';
    case EXTERNAL_ANAL_SPHINCTER = 'external_anal_sphincter';
    case DEEP_TRANSVERSE_PERINEAL = 'deep_transverse_perineal';
    case SUPERFICIAL_TRANSVERSE_PERINEAL = 'superficial_transverse_perineal';
    case BULBOSPONGIOSUS = 'bulbospongiosus';
    case ISCHIOCAVERNOSUS = 'ischiocavernosus';

    // -------------------------------------------------------------------------
    // Hip & Gluteal
    // -------------------------------------------------------------------------
    case GLUTEUS_MAXIMUS = 'gluteus_maximus';
    case GLUTEUS_MEDIUS = 'gluteus_medius';
    case GLUTEUS_MINIMUS = 'gluteus_minimus';
    case TENSOR_FASCIAE_LATAE = 'tensor_fasciae_latae';
    case OBTURATOR_EXTERNUS = 'obturator_externus';
    case GEMELLUS_SUPERIOR = 'gemellus_superior';
    case GEMELLUS_INFERIOR = 'gemellus_inferior';
    case QUADRATUS_FEMORIS = 'quadratus_femoris';

    // -------------------------------------------------------------------------
    // Thigh — Anterior
    // -------------------------------------------------------------------------
    case RECTUS_FEMORIS = 'rectus_femoris';
    case VASTUS_LATERALIS = 'vastus_lateralis';
    case VASTUS_MEDIALIS = 'vastus_medialis';
    case VASTUS_INTERMEDIUS = 'vastus_intermedius';
    case SARTORIUS = 'sartorius';
    case PECTINEUS = 'pectineus';

    // -------------------------------------------------------------------------
    // Thigh — Medial (Adductors)
    // -------------------------------------------------------------------------
    case ADDUCTOR_LONGUS = 'adductor_longus';
    case ADDUCTOR_BREVIS = 'adductor_brevis';
    case ADDUCTOR_MAGNUS = 'adductor_magnus';
    case GRACILIS = 'gracilis';

    // -------------------------------------------------------------------------
    // Thigh — Posterior (Hamstrings)
    // -------------------------------------------------------------------------
    case BICEPS_FEMORIS_LONG_HEAD = 'biceps_femoris_long_head';
    case BICEPS_FEMORIS_SHORT_HEAD = 'biceps_femoris_short_head';
    case SEMITENDINOSUS = 'semitendinosus';
    case SEMIMEMBRANOSUS = 'semimembranosus';

    // -------------------------------------------------------------------------
    // Leg — Anterior
    // -------------------------------------------------------------------------
    case TIBIALIS_ANTERIOR = 'tibialis_anterior';
    case EXTENSOR_HALLUCIS_LONGUS = 'extensor_hallucis_longus';
    case EXTENSOR_DIGITORUM_LONGUS = 'extensor_digitorum_longus';
    case FIBULARIS_TERTIUS = 'fibularis_tertius';

    // -------------------------------------------------------------------------
    // Leg — Lateral
    // -------------------------------------------------------------------------
    case FIBULARIS_LONGUS = 'fibularis_longus';
    case FIBULARIS_BREVIS = 'fibularis_brevis';

    // -------------------------------------------------------------------------
    // Leg — Posterior Superficial
    // -------------------------------------------------------------------------
    case GASTROCNEMIUS_MEDIAL_HEAD = 'gastrocnemius_medial_head';
    case GASTROCNEMIUS_LATERAL_HEAD = 'gastrocnemius_lateral_head';
    case SOLEUS = 'soleus';
    case PLANTARIS = 'plantaris';

    // -------------------------------------------------------------------------
    // Leg — Posterior Deep
    // -------------------------------------------------------------------------
    case POPLITEUS = 'popliteus';
    case FLEXOR_HALLUCIS_LONGUS = 'flexor_hallucis_longus';
    case FLEXOR_DIGITORUM_LONGUS = 'flexor_digitorum_longus';
    case TIBIALIS_POSTERIOR = 'tibialis_posterior';

    // -------------------------------------------------------------------------
    // Foot — Dorsum
    // -------------------------------------------------------------------------
    case EXTENSOR_HALLUCIS_BREVIS = 'extensor_hallucis_brevis';
    case EXTENSOR_DIGITORUM_BREVIS = 'extensor_digitorum_brevis';

    // -------------------------------------------------------------------------
    // Foot — Plantar
    // -------------------------------------------------------------------------
    case ABDUCTOR_HALLUCIS = 'abductor_hallucis';
    case FLEXOR_HALLUCIS_BREVIS = 'flexor_hallucis_brevis';
    case ADDUCTOR_HALLUCIS = 'adductor_hallucis';
    case ABDUCTOR_DIGITI_MINIMI_FOOT = 'abductor_digiti_minimi_foot';
    case FLEXOR_DIGITI_MINIMI_BREVIS_FOOT = 'flexor_digiti_minimi_brevis_foot';
    case FLEXOR_DIGITORUM_BREVIS = 'flexor_digitorum_brevis';
    case QUADRATUS_PLANTAE = 'quadratus_plantae';
    case LUMBRICAL_FOOT_FIRST = 'lumbrical_foot_first';
    case LUMBRICAL_FOOT_SECOND = 'lumbrical_foot_second';
    case LUMBRICAL_FOOT_THIRD = 'lumbrical_foot_third';
    case LUMBRICAL_FOOT_FOURTH = 'lumbrical_foot_fourth';
    case DORSAL_INTEROSSEI_FOOT = 'dorsal_interossei_foot';
    case PLANTAR_INTEROSSEI_FOOT = 'plantar_interossei_foot';

    public function label(): string
    {
        return match ($this) {
            self::FRONTALIS => 'Frontalis',
            self::ORBICULARIS_OCULI => 'Orbicularis Oculi',
            self::ORBICULARIS_ORIS => 'Orbicularis Oris',
            self::ZYGOMATICUS_MAJOR => 'Zygomaticus Major',
            self::ZYGOMATICUS_MINOR => 'Zygomaticus Minor',
            self::BUCCINATOR => 'Buccinator',
            self::MASSETER => 'Masseter',
            self::TEMPORALIS => 'Temporalis',
            self::MEDIAL_PTERYGOID => 'Medial Pterygoid',
            self::LATERAL_PTERYGOID => 'Lateral Pterygoid',
            self::LEVATOR_LABII_SUPERIORIS => 'Levator Labii Superioris',
            self::DEPRESSOR_LABII_INFERIORIS => 'Depressor Labii Inferioris',
            self::DEPRESSOR_ANGULI_ORIS => 'Depressor Anguli Oris',
            self::MENTALIS => 'Mentalis',
            self::NASALIS => 'Nasalis',
            self::CORRUGATOR_SUPERCILII => 'Corrugator Supercilii',
            self::PROCERUS => 'Procerus',
            self::RISORIUS => 'Risorius',
            self::PLATYSMA => 'Platysma',
            self::STERNOCLEIDOMASTOID => 'Sternocleidomastoid',
            self::ANTERIOR_SCALENE => 'Anterior Scalene',
            self::MIDDLE_SCALENE => 'Middle Scalene',
            self::POSTERIOR_SCALENE => 'Posterior Scalene',
            self::LONGUS_COLLI => 'Longus Colli',
            self::LONGUS_CAPITIS => 'Longus Capitis',
            self::RECTUS_CAPITIS_ANTERIOR => 'Rectus Capitis Anterior',
            self::RECTUS_CAPITIS_LATERALIS => 'Rectus Capitis Lateralis',
            self::SPLENIUS_CAPITIS => 'Splenius Capitis',
            self::SPLENIUS_CERVICIS => 'Splenius Cervicis',
            self::SEMISPINALIS_CAPITIS => 'Semispinalis Capitis',
            self::SUBOCCIPITAL_RECTUS_CAPITIS_POSTERIOR_MAJOR => 'Rectus Capitis Posterior Major',
            self::SUBOCCIPITAL_RECTUS_CAPITIS_POSTERIOR_MINOR => 'Rectus Capitis Posterior Minor',
            self::OBLIQUUS_CAPITIS_SUPERIOR => 'Obliquus Capitis Superior',
            self::OBLIQUUS_CAPITIS_INFERIOR => 'Obliquus Capitis Inferior',
            self::MYLOHYOID => 'Mylohyoid',
            self::GENIOHYOID => 'Geniohyoid',
            self::DIGASTRIC_ANTERIOR => 'Digastric — Anterior Belly',
            self::DIGASTRIC_POSTERIOR => 'Digastric — Posterior Belly',
            self::STYLOHYOID => 'Stylohyoid',
            self::OMOHYOID => 'Omohyoid',
            self::STERNOHYOID => 'Sternohyoid',
            self::STERNOTHYROID => 'Sternothyroid',
            self::THYROHYOID => 'Thyrohyoid',
            self::PECTORALIS_MAJOR => 'Pectoralis Major',
            self::PECTORALIS_MINOR => 'Pectoralis Minor',
            self::SERRATUS_ANTERIOR => 'Serratus Anterior',
            self::SUBCLAVIUS => 'Subclavius',
            self::EXTERNAL_INTERCOSTAL => 'External Intercostal',
            self::INTERNAL_INTERCOSTAL => 'Internal Intercostal',
            self::INNERMOST_INTERCOSTAL => 'Innermost Intercostal',
            self::TRANSVERSUS_THORACIS => 'Transversus Thoracis',
            self::LEVATORES_COSTARUM => 'Levatores Costarum',
            self::SUBCOSTALIS => 'Subcostalis',
            self::DIAPHRAGM => 'Diaphragm',
            self::RECTUS_ABDOMINIS => 'Rectus Abdominis',
            self::EXTERNAL_OBLIQUE => 'External Oblique',
            self::INTERNAL_OBLIQUE => 'Internal Oblique',
            self::TRANSVERSUS_ABDOMINIS => 'Transversus Abdominis',
            self::PYRAMIDALIS => 'Pyramidalis',
            self::QUADRATUS_LUMBORUM => 'Quadratus Lumborum',
            self::PSOAS_MAJOR => 'Psoas Major',
            self::PSOAS_MINOR => 'Psoas Minor',
            self::ILIACUS => 'Iliacus',
            self::CREMASTER => 'Cremaster',
            self::TRAPEZIUS_UPPER => 'Trapezius — Upper Fibers',
            self::TRAPEZIUS_MIDDLE => 'Trapezius — Middle Fibers',
            self::TRAPEZIUS_LOWER => 'Trapezius — Lower Fibers',
            self::LATISSIMUS_DORSI => 'Latissimus Dorsi',
            self::RHOMBOID_MAJOR => 'Rhomboid Major',
            self::RHOMBOID_MINOR => 'Rhomboid Minor',
            self::LEVATOR_SCAPULAE => 'Levator Scapulae',
            self::SERRATUS_POSTERIOR_SUPERIOR => 'Serratus Posterior Superior',
            self::SERRATUS_POSTERIOR_INFERIOR => 'Serratus Posterior Inferior',
            self::ERECTOR_SPINAE_ILIOCOSTALIS => 'Erector Spinae — Iliocostalis',
            self::ERECTOR_SPINAE_LONGISSIMUS => 'Erector Spinae — Longissimus',
            self::ERECTOR_SPINAE_SPINALIS => 'Erector Spinae — Spinalis',
            self::SEMISPINALIS_THORACIS => 'Semispinalis Thoracis',
            self::SEMISPINALIS_CERVICIS => 'Semispinalis Cervicis',
            self::MULTIFIDUS => 'Multifidus',
            self::ROTATORES => 'Rotatores',
            self::INTERSPINALES => 'Interspinales',
            self::INTERTRANSVERSARII => 'Intertransversarii',
            self::DELTOID_ANTERIOR => 'Deltoid — Anterior',
            self::DELTOID_MIDDLE => 'Deltoid — Middle',
            self::DELTOID_POSTERIOR => 'Deltoid — Posterior',
            self::SUPRASPINATUS => 'Supraspinatus',
            self::INFRASPINATUS => 'Infraspinatus',
            self::TERES_MINOR => 'Teres Minor',
            self::TERES_MAJOR => 'Teres Major',
            self::SUBSCAPULARIS => 'Subscapularis',
            self::CORACOBRACHIALIS => 'Coracobrachialis',
            self::BICEPS_BRACHII_LONG_HEAD => 'Biceps Brachii — Long Head',
            self::BICEPS_BRACHII_SHORT_HEAD => 'Biceps Brachii — Short Head',
            self::BRACHIALIS => 'Brachialis',
            self::TRICEPS_BRACHII_LONG_HEAD => 'Triceps Brachii — Long Head',
            self::TRICEPS_BRACHII_MEDIAL_HEAD => 'Triceps Brachii — Medial Head',
            self::TRICEPS_BRACHII_LATERAL_HEAD => 'Triceps Brachii — Lateral Head',
            self::ANCONEUS => 'Anconeus',
            self::PRONATOR_TERES => 'Pronator Teres',
            self::FLEXOR_CARPI_RADIALIS => 'Flexor Carpi Radialis',
            self::PALMARIS_LONGUS => 'Palmaris Longus',
            self::FLEXOR_CARPI_ULNARIS => 'Flexor Carpi Ulnaris',
            self::FLEXOR_DIGITORUM_SUPERFICIALIS => 'Flexor Digitorum Superficialis',
            self::FLEXOR_DIGITORUM_PROFUNDUS => 'Flexor Digitorum Profundus',
            self::FLEXOR_POLLICIS_LONGUS => 'Flexor Pollicis Longus',
            self::PRONATOR_QUADRATUS => 'Pronator Quadratus',
            self::BRACHIORADIALIS => 'Brachioradialis',
            self::EXTENSOR_CARPI_RADIALIS_LONGUS => 'Extensor Carpi Radialis Longus',
            self::EXTENSOR_CARPI_RADIALIS_BREVIS => 'Extensor Carpi Radialis Brevis',
            self::EXTENSOR_DIGITORUM => 'Extensor Digitorum',
            self::EXTENSOR_DIGITI_MINIMI => 'Extensor Digiti Minimi',
            self::EXTENSOR_CARPI_ULNARIS => 'Extensor Carpi Ulnaris',
            self::SUPINATOR => 'Supinator',
            self::ABDUCTOR_POLLICIS_LONGUS => 'Abductor Pollicis Longus',
            self::EXTENSOR_POLLICIS_BREVIS => 'Extensor Pollicis Brevis',
            self::EXTENSOR_POLLICIS_LONGUS => 'Extensor Pollicis Longus',
            self::EXTENSOR_INDICIS => 'Extensor Indicis',
            self::ABDUCTOR_POLLICIS_BREVIS => 'Abductor Pollicis Brevis',
            self::FLEXOR_POLLICIS_BREVIS => 'Flexor Pollicis Brevis',
            self::OPPONENS_POLLICIS => 'Opponens Pollicis',
            self::ADDUCTOR_POLLICIS => 'Adductor Pollicis',
            self::ABDUCTOR_DIGITI_MINIMI_HAND => 'Abductor Digiti Minimi (Hand)',
            self::FLEXOR_DIGITI_MINIMI_BREVIS_HAND => 'Flexor Digiti Minimi Brevis (Hand)',
            self::OPPONENS_DIGITI_MINIMI => 'Opponens Digiti Minimi',
            self::PALMARIS_BREVIS => 'Palmaris Brevis',
            self::LUMBRICAL_HAND_FIRST => 'First Lumbrical (Hand)',
            self::LUMBRICAL_HAND_SECOND => 'Second Lumbrical (Hand)',
            self::LUMBRICAL_HAND_THIRD => 'Third Lumbrical (Hand)',
            self::LUMBRICAL_HAND_FOURTH => 'Fourth Lumbrical (Hand)',
            self::DORSAL_INTEROSSEI_HAND => 'Dorsal Interossei (Hand)',
            self::PALMAR_INTEROSSEI_HAND => 'Palmar Interossei (Hand)',
            self::LEVATOR_ANI_PUBOCOCCYGEUS => 'Levator Ani — Pubococcygeus',
            self::LEVATOR_ANI_ILIOCOCCYGEUS => 'Levator Ani — Iliococcygeus',
            self::LEVATOR_ANI_PUBORECTALIS => 'Levator Ani — Puborectalis',
            self::COCCYGEUS => 'Coccygeus',
            self::OBTURATOR_INTERNUS => 'Obturator Internus',
            self::PIRIFORMIS => 'Piriformis',
            self::EXTERNAL_URETHRAL_SPHINCTER => 'External Urethral Sphincter',
            self::EXTERNAL_ANAL_SPHINCTER => 'External Anal Sphincter',
            self::DEEP_TRANSVERSE_PERINEAL => 'Deep Transverse Perineal',
            self::SUPERFICIAL_TRANSVERSE_PERINEAL => 'Superficial Transverse Perineal',
            self::BULBOSPONGIOSUS => 'Bulbospongiosus',
            self::ISCHIOCAVERNOSUS => 'Ischiocavernosus',
            self::GLUTEUS_MAXIMUS => 'Gluteus Maximus',
            self::GLUTEUS_MEDIUS => 'Gluteus Medius',
            self::GLUTEUS_MINIMUS => 'Gluteus Minimus',
            self::TENSOR_FASCIAE_LATAE => 'Tensor Fasciae Latae',
            self::OBTURATOR_EXTERNUS => 'Obturator Externus',
            self::GEMELLUS_SUPERIOR => 'Gemellus Superior',
            self::GEMELLUS_INFERIOR => 'Gemellus Inferior',
            self::QUADRATUS_FEMORIS => 'Quadratus Femoris',
            self::RECTUS_FEMORIS => 'Rectus Femoris',
            self::VASTUS_LATERALIS => 'Vastus Lateralis',
            self::VASTUS_MEDIALIS => 'Vastus Medialis',
            self::VASTUS_INTERMEDIUS => 'Vastus Intermedius',
            self::SARTORIUS => 'Sartorius',
            self::PECTINEUS => 'Pectineus',
            self::ADDUCTOR_LONGUS => 'Adductor Longus',
            self::ADDUCTOR_BREVIS => 'Adductor Brevis',
            self::ADDUCTOR_MAGNUS => 'Adductor Magnus',
            self::GRACILIS => 'Gracilis',
            self::BICEPS_FEMORIS_LONG_HEAD => 'Biceps Femoris — Long Head',
            self::BICEPS_FEMORIS_SHORT_HEAD => 'Biceps Femoris — Short Head',
            self::SEMITENDINOSUS => 'Semitendinosus',
            self::SEMIMEMBRANOSUS => 'Semimembranosus',
            self::TIBIALIS_ANTERIOR => 'Tibialis Anterior',
            self::EXTENSOR_HALLUCIS_LONGUS => 'Extensor Hallucis Longus',
            self::EXTENSOR_DIGITORUM_LONGUS => 'Extensor Digitorum Longus',
            self::FIBULARIS_TERTIUS => 'Fibularis (Peroneus) Tertius',
            self::FIBULARIS_LONGUS => 'Fibularis (Peroneus) Longus',
            self::FIBULARIS_BREVIS => 'Fibularis (Peroneus) Brevis',
            self::GASTROCNEMIUS_MEDIAL_HEAD => 'Gastrocnemius — Medial Head',
            self::GASTROCNEMIUS_LATERAL_HEAD => 'Gastrocnemius — Lateral Head',
            self::SOLEUS => 'Soleus',
            self::PLANTARIS => 'Plantaris',
            self::POPLITEUS => 'Popliteus',
            self::FLEXOR_HALLUCIS_LONGUS => 'Flexor Hallucis Longus',
            self::FLEXOR_DIGITORUM_LONGUS => 'Flexor Digitorum Longus',
            self::TIBIALIS_POSTERIOR => 'Tibialis Posterior',
            self::EXTENSOR_HALLUCIS_BREVIS => 'Extensor Hallucis Brevis',
            self::EXTENSOR_DIGITORUM_BREVIS => 'Extensor Digitorum Brevis',
            self::ABDUCTOR_HALLUCIS => 'Abductor Hallucis',
            self::FLEXOR_HALLUCIS_BREVIS => 'Flexor Hallucis Brevis',
            self::ADDUCTOR_HALLUCIS => 'Adductor Hallucis',
            self::ABDUCTOR_DIGITI_MINIMI_FOOT => 'Abductor Digiti Minimi (Foot)',
            self::FLEXOR_DIGITI_MINIMI_BREVIS_FOOT => 'Flexor Digiti Minimi Brevis (Foot)',
            self::FLEXOR_DIGITORUM_BREVIS => 'Flexor Digitorum Brevis',
            self::QUADRATUS_PLANTAE => 'Quadratus Plantae',
            self::LUMBRICAL_FOOT_FIRST => 'First Lumbrical (Foot)',
            self::LUMBRICAL_FOOT_SECOND => 'Second Lumbrical (Foot)',
            self::LUMBRICAL_FOOT_THIRD => 'Third Lumbrical (Foot)',
            self::LUMBRICAL_FOOT_FOURTH => 'Fourth Lumbrical (Foot)',
            self::DORSAL_INTEROSSEI_FOOT => 'Dorsal Interossei (Foot)',
            self::PLANTAR_INTEROSSEI_FOOT => 'Plantar Interossei (Foot)',
        };
    }

    public function category(): string
    {
        return match (true) {
            in_array($this, [
                self::FRONTALIS,
                self::ORBICULARIS_OCULI,
                self::ORBICULARIS_ORIS,
                self::ZYGOMATICUS_MAJOR,
                self::ZYGOMATICUS_MINOR,
                self::BUCCINATOR,
                self::MASSETER,
                self::TEMPORALIS,
                self::MEDIAL_PTERYGOID,
                self::LATERAL_PTERYGOID,
                self::LEVATOR_LABII_SUPERIORIS,
                self::DEPRESSOR_LABII_INFERIORIS,
                self::DEPRESSOR_ANGULI_ORIS,
                self::MENTALIS,
                self::NASALIS,
                self::CORRUGATOR_SUPERCILII,
                self::PROCERUS,
                self::RISORIUS,
                self::PLATYSMA,
            ], true) => 'Head and Face',

            in_array($this, [
                self::STERNOCLEIDOMASTOID,
                self::ANTERIOR_SCALENE,
                self::MIDDLE_SCALENE,
                self::POSTERIOR_SCALENE,
                self::LONGUS_COLLI,
                self::LONGUS_CAPITIS,
                self::RECTUS_CAPITIS_ANTERIOR,
                self::RECTUS_CAPITIS_LATERALIS,
                self::SPLENIUS_CAPITIS,
                self::SPLENIUS_CERVICIS,
                self::SEMISPINALIS_CAPITIS,
                self::SUBOCCIPITAL_RECTUS_CAPITIS_POSTERIOR_MAJOR,
                self::SUBOCCIPITAL_RECTUS_CAPITIS_POSTERIOR_MINOR,
                self::OBLIQUUS_CAPITIS_SUPERIOR,
                self::OBLIQUUS_CAPITIS_INFERIOR,
                self::MYLOHYOID,
                self::GENIOHYOID,
                self::DIGASTRIC_ANTERIOR,
                self::DIGASTRIC_POSTERIOR,
                self::STYLOHYOID,
                self::OMOHYOID,
                self::STERNOHYOID,
                self::STERNOTHYROID,
                self::THYROHYOID,
            ], true) => 'Neck',

            in_array($this, [
                self::PECTORALIS_MAJOR,
                self::PECTORALIS_MINOR,
                self::SERRATUS_ANTERIOR,
                self::SUBCLAVIUS,
                self::EXTERNAL_INTERCOSTAL,
                self::INTERNAL_INTERCOSTAL,
                self::INNERMOST_INTERCOSTAL,
                self::TRANSVERSUS_THORACIS,
                self::LEVATORES_COSTARUM,
                self::SUBCOSTALIS,
                self::DIAPHRAGM,
            ], true) => 'Thorax',

            in_array($this, [
                self::RECTUS_ABDOMINIS,
                self::EXTERNAL_OBLIQUE,
                self::INTERNAL_OBLIQUE,
                self::TRANSVERSUS_ABDOMINIS,
                self::PYRAMIDALIS,
                self::QUADRATUS_LUMBORUM,
                self::PSOAS_MAJOR,
                self::PSOAS_MINOR,
                self::ILIACUS,
                self::CREMASTER,
            ], true) => 'Abdomen',

            in_array($this, [
                self::TRAPEZIUS_UPPER,
                self::TRAPEZIUS_MIDDLE,
                self::TRAPEZIUS_LOWER,
                self::LATISSIMUS_DORSI,
                self::RHOMBOID_MAJOR,
                self::RHOMBOID_MINOR,
                self::LEVATOR_SCAPULAE,
                self::SERRATUS_POSTERIOR_SUPERIOR,
                self::SERRATUS_POSTERIOR_INFERIOR,
                self::ERECTOR_SPINAE_ILIOCOSTALIS,
                self::ERECTOR_SPINAE_LONGISSIMUS,
                self::ERECTOR_SPINAE_SPINALIS,
                self::SEMISPINALIS_THORACIS,
                self::SEMISPINALIS_CERVICIS,
                self::MULTIFIDUS,
                self::ROTATORES,
                self::INTERSPINALES,
                self::INTERTRANSVERSARII,
            ], true) => 'Back and Spine',

            in_array($this, [
                self::DELTOID_ANTERIOR,
                self::DELTOID_MIDDLE,
                self::DELTOID_POSTERIOR,
                self::SUPRASPINATUS,
                self::INFRASPINATUS,
                self::TERES_MINOR,
                self::TERES_MAJOR,
                self::SUBSCAPULARIS,
                self::CORACOBRACHIALIS,
            ], true) => 'Shoulder',

            in_array($this, [
                self::BICEPS_BRACHII_LONG_HEAD,
                self::BICEPS_BRACHII_SHORT_HEAD,
                self::BRACHIALIS,
                self::TRICEPS_BRACHII_LONG_HEAD,
                self::TRICEPS_BRACHII_MEDIAL_HEAD,
                self::TRICEPS_BRACHII_LATERAL_HEAD,
                self::ANCONEUS,
            ], true) => 'Upper Arm',

            in_array($this, [
                self::PRONATOR_TERES,
                self::FLEXOR_CARPI_RADIALIS,
                self::PALMARIS_LONGUS,
                self::FLEXOR_CARPI_ULNARIS,
                self::FLEXOR_DIGITORUM_SUPERFICIALIS,
                self::FLEXOR_DIGITORUM_PROFUNDUS,
                self::FLEXOR_POLLICIS_LONGUS,
                self::PRONATOR_QUADRATUS,
                self::BRACHIORADIALIS,
                self::EXTENSOR_CARPI_RADIALIS_LONGUS,
                self::EXTENSOR_CARPI_RADIALIS_BREVIS,
                self::EXTENSOR_DIGITORUM,
                self::EXTENSOR_DIGITI_MINIMI,
                self::EXTENSOR_CARPI_ULNARIS,
                self::SUPINATOR,
                self::ABDUCTOR_POLLICIS_LONGUS,
                self::EXTENSOR_POLLICIS_BREVIS,
                self::EXTENSOR_POLLICIS_LONGUS,
                self::EXTENSOR_INDICIS,
            ], true) => 'Forearm',

            in_array($this, [
                self::ABDUCTOR_POLLICIS_BREVIS,
                self::FLEXOR_POLLICIS_BREVIS,
                self::OPPONENS_POLLICIS,
                self::ADDUCTOR_POLLICIS,
                self::ABDUCTOR_DIGITI_MINIMI_HAND,
                self::FLEXOR_DIGITI_MINIMI_BREVIS_HAND,
                self::OPPONENS_DIGITI_MINIMI,
                self::PALMARIS_BREVIS,
                self::LUMBRICAL_HAND_FIRST,
                self::LUMBRICAL_HAND_SECOND,
                self::LUMBRICAL_HAND_THIRD,
                self::LUMBRICAL_HAND_FOURTH,
                self::DORSAL_INTEROSSEI_HAND,
                self::PALMAR_INTEROSSEI_HAND,
            ], true) => 'Hand',

            in_array($this, [
                self::LEVATOR_ANI_PUBOCOCCYGEUS,
                self::LEVATOR_ANI_ILIOCOCCYGEUS,
                self::LEVATOR_ANI_PUBORECTALIS,
                self::COCCYGEUS,
                self::OBTURATOR_INTERNUS,
                self::PIRIFORMIS,
                self::EXTERNAL_URETHRAL_SPHINCTER,
                self::EXTERNAL_ANAL_SPHINCTER,
                self::DEEP_TRANSVERSE_PERINEAL,
                self::SUPERFICIAL_TRANSVERSE_PERINEAL,
                self::BULBOSPONGIOSUS,
                self::ISCHIOCAVERNOSUS,
            ], true) => 'Pelvis and Perineum',

            in_array($this, [
                self::GLUTEUS_MAXIMUS,
                self::GLUTEUS_MEDIUS,
                self::GLUTEUS_MINIMUS,
                self::TENSOR_FASCIAE_LATAE,
                self::OBTURATOR_EXTERNUS,
                self::GEMELLUS_SUPERIOR,
                self::GEMELLUS_INFERIOR,
                self::QUADRATUS_FEMORIS,
            ], true) => 'Hip and Gluteal',

            in_array($this, [
                self::RECTUS_FEMORIS,
                self::VASTUS_LATERALIS,
                self::VASTUS_MEDIALIS,
                self::VASTUS_INTERMEDIUS,
                self::SARTORIUS,
                self::PECTINEUS,
                self::ADDUCTOR_LONGUS,
                self::ADDUCTOR_BREVIS,
                self::ADDUCTOR_MAGNUS,
                self::GRACILIS,
                self::BICEPS_FEMORIS_LONG_HEAD,
                self::BICEPS_FEMORIS_SHORT_HEAD,
                self::SEMITENDINOSUS,
                self::SEMIMEMBRANOSUS,
            ], true) => 'Thigh',

            in_array($this, [
                self::TIBIALIS_ANTERIOR,
                self::EXTENSOR_HALLUCIS_LONGUS,
                self::EXTENSOR_DIGITORUM_LONGUS,
                self::FIBULARIS_TERTIUS,
                self::FIBULARIS_LONGUS,
                self::FIBULARIS_BREVIS,
                self::GASTROCNEMIUS_MEDIAL_HEAD,
                self::GASTROCNEMIUS_LATERAL_HEAD,
                self::SOLEUS,
                self::PLANTARIS,
                self::POPLITEUS,
                self::FLEXOR_HALLUCIS_LONGUS,
                self::FLEXOR_DIGITORUM_LONGUS,
                self::TIBIALIS_POSTERIOR,
            ], true) => 'Leg',

            default => 'Foot',
        };
    }

    public function isRotatorCuff(): bool
    {
        return in_array($this, [
            self::SUPRASPINATUS,
            self::INFRASPINATUS,
            self::TERES_MINOR,
            self::SUBSCAPULARIS,
        ], true);
    }

    public function isHamstring(): bool
    {
        return in_array($this, [
            self::BICEPS_FEMORIS_LONG_HEAD,
            self::BICEPS_FEMORIS_SHORT_HEAD,
            self::SEMITENDINOSUS,
            self::SEMIMEMBRANOSUS,
        ], true);
    }

    public function isQuadriceps(): bool
    {
        return in_array($this, [
            self::RECTUS_FEMORIS,
            self::VASTUS_LATERALIS,
            self::VASTUS_MEDIALIS,
            self::VASTUS_INTERMEDIUS,
        ], true);
    }

    public function isErectorSpinae(): bool
    {
        return in_array($this, [
            self::ERECTOR_SPINAE_ILIOCOSTALIS,
            self::ERECTOR_SPINAE_LONGISSIMUS,
            self::ERECTOR_SPINAE_SPINALIS,
        ], true);
    }

    public function isDeepStabilizer(): bool
    {
        return in_array($this, [
            self::TRANSVERSUS_ABDOMINIS,
            self::MULTIFIDUS,
            self::DIAPHRAGM,
            self::LEVATOR_ANI_PUBOCOCCYGEUS,
            self::LEVATOR_ANI_ILIOCOCCYGEUS,
            self::LEVATOR_ANI_PUBORECTALIS,
        ], true);
    }

    /** @return list<self> */
    public static function byCategory(string $category): array
    {
        return array_values(
            array_filter(self::cases(), fn(self $case) => $case->category() === $category)
        );
    }
}
