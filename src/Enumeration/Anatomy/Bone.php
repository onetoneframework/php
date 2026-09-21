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

enum Bone: string
{
    // -------------------------------------------------------------------------
    // Skull — Cranium
    // -------------------------------------------------------------------------
    case FRONTAL = 'frontal';
    case PARIETAL_LEFT = 'parietal_left';
    case PARIETAL_RIGHT = 'parietal_right';
    case TEMPORAL_LEFT = 'temporal_left';
    case TEMPORAL_RIGHT = 'temporal_right';
    case OCCIPITAL = 'occipital';
    case SPHENOID = 'sphenoid';
    case ETHMOID = 'ethmoid';

    // -------------------------------------------------------------------------
    // Skull — Facial
    // -------------------------------------------------------------------------
    case MAXILLA_LEFT = 'maxilla_left';
    case MAXILLA_RIGHT = 'maxilla_right';
    case MANDIBLE = 'mandible';
    case ZYGOMATIC_LEFT = 'zygomatic_left';
    case ZYGOMATIC_RIGHT = 'zygomatic_right';
    case NASAL_LEFT = 'nasal_left';
    case NASAL_RIGHT = 'nasal_right';
    case LACRIMAL_LEFT = 'lacrimal_left';
    case LACRIMAL_RIGHT = 'lacrimal_right';
    case PALATINE_LEFT = 'palatine_left';
    case PALATINE_RIGHT = 'palatine_right';
    case INFERIOR_NASAL_CONCHA_LEFT = 'inferior_nasal_concha_left';
    case INFERIOR_NASAL_CONCHA_RIGHT = 'inferior_nasal_concha_right';
    case VOMER = 'vomer';

    // -------------------------------------------------------------------------
    // Skull — Auditory Ossicles
    // -------------------------------------------------------------------------
    case MALLEUS_LEFT = 'malleus_left';
    case MALLEUS_RIGHT = 'malleus_right';
    case INCUS_LEFT = 'incus_left';
    case INCUS_RIGHT = 'incus_right';
    case STAPES_LEFT = 'stapes_left';
    case STAPES_RIGHT = 'stapes_right';

    // -------------------------------------------------------------------------
    // Hyoid
    // -------------------------------------------------------------------------
    case HYOID = 'hyoid';

    // -------------------------------------------------------------------------
    // Vertebral Column — Cervical
    // -------------------------------------------------------------------------
    case CERVICAL_VERTEBRA_C1_ATLAS = 'cervical_vertebra_c1_atlas';
    case CERVICAL_VERTEBRA_C2_AXIS = 'cervical_vertebra_c2_axis';
    case CERVICAL_VERTEBRA_C3 = 'cervical_vertebra_c3';
    case CERVICAL_VERTEBRA_C4 = 'cervical_vertebra_c4';
    case CERVICAL_VERTEBRA_C5 = 'cervical_vertebra_c5';
    case CERVICAL_VERTEBRA_C6 = 'cervical_vertebra_c6';
    case CERVICAL_VERTEBRA_C7 = 'cervical_vertebra_c7';

    // -------------------------------------------------------------------------
    // Vertebral Column — Thoracic
    // -------------------------------------------------------------------------
    case THORACIC_VERTEBRA_T1 = 'thoracic_vertebra_t1';
    case THORACIC_VERTEBRA_T2 = 'thoracic_vertebra_t2';
    case THORACIC_VERTEBRA_T3 = 'thoracic_vertebra_t3';
    case THORACIC_VERTEBRA_T4 = 'thoracic_vertebra_t4';
    case THORACIC_VERTEBRA_T5 = 'thoracic_vertebra_t5';
    case THORACIC_VERTEBRA_T6 = 'thoracic_vertebra_t6';
    case THORACIC_VERTEBRA_T7 = 'thoracic_vertebra_t7';
    case THORACIC_VERTEBRA_T8 = 'thoracic_vertebra_t8';
    case THORACIC_VERTEBRA_T9 = 'thoracic_vertebra_t9';
    case THORACIC_VERTEBRA_T10 = 'thoracic_vertebra_t10';
    case THORACIC_VERTEBRA_T11 = 'thoracic_vertebra_t11';
    case THORACIC_VERTEBRA_T12 = 'thoracic_vertebra_t12';

    // -------------------------------------------------------------------------
    // Vertebral Column — Lumbar
    // -------------------------------------------------------------------------
    case LUMBAR_VERTEBRA_L1 = 'lumbar_vertebra_l1';
    case LUMBAR_VERTEBRA_L2 = 'lumbar_vertebra_l2';
    case LUMBAR_VERTEBRA_L3 = 'lumbar_vertebra_l3';
    case LUMBAR_VERTEBRA_L4 = 'lumbar_vertebra_l4';
    case LUMBAR_VERTEBRA_L5 = 'lumbar_vertebra_l5';

    // -------------------------------------------------------------------------
    // Vertebral Column — Sacrum & Coccyx
    // -------------------------------------------------------------------------
    case SACRUM = 'sacrum';
    case COCCYX = 'coccyx';

    // -------------------------------------------------------------------------
    // Thoracic Cage
    // -------------------------------------------------------------------------
    case STERNUM_MANUBRIUM = 'sternum_manubrium';
    case STERNUM_BODY = 'sternum_body';
    case STERNUM_XIPHOID_PROCESS = 'sternum_xiphoid_process';
    case RIB_1_LEFT = 'rib_1_left';
    case RIB_1_RIGHT = 'rib_1_right';
    case RIB_2_LEFT = 'rib_2_left';
    case RIB_2_RIGHT = 'rib_2_right';
    case RIB_3_LEFT = 'rib_3_left';
    case RIB_3_RIGHT = 'rib_3_right';
    case RIB_4_LEFT = 'rib_4_left';
    case RIB_4_RIGHT = 'rib_4_right';
    case RIB_5_LEFT = 'rib_5_left';
    case RIB_5_RIGHT = 'rib_5_right';
    case RIB_6_LEFT = 'rib_6_left';
    case RIB_6_RIGHT = 'rib_6_right';
    case RIB_7_LEFT = 'rib_7_left';
    case RIB_7_RIGHT = 'rib_7_right';
    case RIB_8_LEFT = 'rib_8_left';
    case RIB_8_RIGHT = 'rib_8_right';
    case RIB_9_LEFT = 'rib_9_left';
    case RIB_9_RIGHT = 'rib_9_right';
    case RIB_10_LEFT = 'rib_10_left';
    case RIB_10_RIGHT = 'rib_10_right';
    case RIB_11_LEFT = 'rib_11_left';
    case RIB_11_RIGHT = 'rib_11_right';
    case RIB_12_LEFT = 'rib_12_left';
    case RIB_12_RIGHT = 'rib_12_right';

    // -------------------------------------------------------------------------
    // Pectoral Girdle
    // -------------------------------------------------------------------------
    case CLAVICLE_LEFT = 'clavicle_left';
    case CLAVICLE_RIGHT = 'clavicle_right';
    case SCAPULA_LEFT = 'scapula_left';
    case SCAPULA_RIGHT = 'scapula_right';

    // -------------------------------------------------------------------------
    // Upper Limb — Arm
    // -------------------------------------------------------------------------
    case HUMERUS_LEFT = 'humerus_left';
    case HUMERUS_RIGHT = 'humerus_right';

    // -------------------------------------------------------------------------
    // Upper Limb — Forearm
    // -------------------------------------------------------------------------
    case RADIUS_LEFT = 'radius_left';
    case RADIUS_RIGHT = 'radius_right';
    case ULNA_LEFT = 'ulna_left';
    case ULNA_RIGHT = 'ulna_right';

    // -------------------------------------------------------------------------
    // Upper Limb — Wrist (Carpals)
    // -------------------------------------------------------------------------
    case SCAPHOID_LEFT = 'scaphoid_left';
    case SCAPHOID_RIGHT = 'scaphoid_right';
    case LUNATE_LEFT = 'lunate_left';
    case LUNATE_RIGHT = 'lunate_right';
    case TRIQUETRUM_LEFT = 'triquetrum_left';
    case TRIQUETRUM_RIGHT = 'triquetrum_right';
    case PISIFORM_LEFT = 'pisiform_left';
    case PISIFORM_RIGHT = 'pisiform_right';
    case TRAPEZIUM_LEFT = 'trapezium_left';
    case TRAPEZIUM_RIGHT = 'trapezium_right';
    case TRAPEZOID_LEFT = 'trapezoid_left';
    case TRAPEZOID_RIGHT = 'trapezoid_right';
    case CAPITATE_LEFT = 'capitate_left';
    case CAPITATE_RIGHT = 'capitate_right';
    case HAMATE_LEFT = 'hamate_left';
    case HAMATE_RIGHT = 'hamate_right';

    // -------------------------------------------------------------------------
    // Upper Limb — Hand (Metacarpals)
    // -------------------------------------------------------------------------
    case METACARPAL_1_LEFT = 'metacarpal_1_left';
    case METACARPAL_1_RIGHT = 'metacarpal_1_right';
    case METACARPAL_2_LEFT = 'metacarpal_2_left';
    case METACARPAL_2_RIGHT = 'metacarpal_2_right';
    case METACARPAL_3_LEFT = 'metacarpal_3_left';
    case METACARPAL_3_RIGHT = 'metacarpal_3_right';
    case METACARPAL_4_LEFT = 'metacarpal_4_left';
    case METACARPAL_4_RIGHT = 'metacarpal_4_right';
    case METACARPAL_5_LEFT = 'metacarpal_5_left';
    case METACARPAL_5_RIGHT = 'metacarpal_5_right';

    // -------------------------------------------------------------------------
    // Upper Limb — Hand (Phalanges)
    // -------------------------------------------------------------------------
    case PROXIMAL_PHALANX_THUMB_LEFT = 'proximal_phalanx_thumb_left';
    case PROXIMAL_PHALANX_THUMB_RIGHT = 'proximal_phalanx_thumb_right';
    case DISTAL_PHALANX_THUMB_LEFT = 'distal_phalanx_thumb_left';
    case DISTAL_PHALANX_THUMB_RIGHT = 'distal_phalanx_thumb_right';
    case PROXIMAL_PHALANX_INDEX_LEFT = 'proximal_phalanx_index_left';
    case PROXIMAL_PHALANX_INDEX_RIGHT = 'proximal_phalanx_index_right';
    case MIDDLE_PHALANX_INDEX_LEFT = 'middle_phalanx_index_left';
    case MIDDLE_PHALANX_INDEX_RIGHT = 'middle_phalanx_index_right';
    case DISTAL_PHALANX_INDEX_LEFT = 'distal_phalanx_index_left';
    case DISTAL_PHALANX_INDEX_RIGHT = 'distal_phalanx_index_right';
    case PROXIMAL_PHALANX_MIDDLE_LEFT = 'proximal_phalanx_middle_left';
    case PROXIMAL_PHALANX_MIDDLE_RIGHT = 'proximal_phalanx_middle_right';
    case MIDDLE_PHALANX_MIDDLE_LEFT = 'middle_phalanx_middle_left';
    case MIDDLE_PHALANX_MIDDLE_RIGHT = 'middle_phalanx_middle_right';
    case DISTAL_PHALANX_MIDDLE_LEFT = 'distal_phalanx_middle_left';
    case DISTAL_PHALANX_MIDDLE_RIGHT = 'distal_phalanx_middle_right';
    case PROXIMAL_PHALANX_RING_LEFT = 'proximal_phalanx_ring_left';
    case PROXIMAL_PHALANX_RING_RIGHT = 'proximal_phalanx_ring_right';
    case MIDDLE_PHALANX_RING_LEFT = 'middle_phalanx_ring_left';
    case MIDDLE_PHALANX_RING_RIGHT = 'middle_phalanx_ring_right';
    case DISTAL_PHALANX_RING_LEFT = 'distal_phalanx_ring_left';
    case DISTAL_PHALANX_RING_RIGHT = 'distal_phalanx_ring_right';
    case PROXIMAL_PHALANX_LITTLE_LEFT = 'proximal_phalanx_little_left';
    case PROXIMAL_PHALANX_LITTLE_RIGHT = 'proximal_phalanx_little_right';
    case MIDDLE_PHALANX_LITTLE_LEFT = 'middle_phalanx_little_left';
    case MIDDLE_PHALANX_LITTLE_RIGHT = 'middle_phalanx_little_right';
    case DISTAL_PHALANX_LITTLE_LEFT = 'distal_phalanx_little_left';
    case DISTAL_PHALANX_LITTLE_RIGHT = 'distal_phalanx_little_right';

    // -------------------------------------------------------------------------
    // Pelvic Girdle
    // -------------------------------------------------------------------------
    case ILIUM_LEFT = 'ilium_left';
    case ILIUM_RIGHT = 'ilium_right';
    case ISCHIUM_LEFT = 'ischium_left';
    case ISCHIUM_RIGHT = 'ischium_right';
    case PUBIS_LEFT = 'pubis_left';
    case PUBIS_RIGHT = 'pubis_right';

    // -------------------------------------------------------------------------
    // Lower Limb — Thigh
    // -------------------------------------------------------------------------
    case FEMUR_LEFT = 'femur_left';
    case FEMUR_RIGHT = 'femur_right';
    case PATELLA_LEFT = 'patella_left';
    case PATELLA_RIGHT = 'patella_right';

    // -------------------------------------------------------------------------
    // Lower Limb — Leg
    // -------------------------------------------------------------------------
    case TIBIA_LEFT = 'tibia_left';
    case TIBIA_RIGHT = 'tibia_right';
    case FIBULA_LEFT = 'fibula_left';
    case FIBULA_RIGHT = 'fibula_right';

    // -------------------------------------------------------------------------
    // Lower Limb — Ankle (Tarsals)
    // -------------------------------------------------------------------------
    case CALCANEUS_LEFT = 'calcaneus_left';
    case CALCANEUS_RIGHT = 'calcaneus_right';
    case TALUS_LEFT = 'talus_left';
    case TALUS_RIGHT = 'talus_right';
    case NAVICULAR_LEFT = 'navicular_left';
    case NAVICULAR_RIGHT = 'navicular_right';
    case MEDIAL_CUNEIFORM_LEFT = 'medial_cuneiform_left';
    case MEDIAL_CUNEIFORM_RIGHT = 'medial_cuneiform_right';
    case INTERMEDIATE_CUNEIFORM_LEFT = 'intermediate_cuneiform_left';
    case INTERMEDIATE_CUNEIFORM_RIGHT = 'intermediate_cuneiform_right';
    case LATERAL_CUNEIFORM_LEFT = 'lateral_cuneiform_left';
    case LATERAL_CUNEIFORM_RIGHT = 'lateral_cuneiform_right';
    case CUBOID_LEFT = 'cuboid_left';
    case CUBOID_RIGHT = 'cuboid_right';

    // -------------------------------------------------------------------------
    // Lower Limb — Foot (Metatarsals)
    // -------------------------------------------------------------------------
    case METATARSAL_1_LEFT = 'metatarsal_1_left';
    case METATARSAL_1_RIGHT = 'metatarsal_1_right';
    case METATARSAL_2_LEFT = 'metatarsal_2_left';
    case METATARSAL_2_RIGHT = 'metatarsal_2_right';
    case METATARSAL_3_LEFT = 'metatarsal_3_left';
    case METATARSAL_3_RIGHT = 'metatarsal_3_right';
    case METATARSAL_4_LEFT = 'metatarsal_4_left';
    case METATARSAL_4_RIGHT = 'metatarsal_4_right';
    case METATARSAL_5_LEFT = 'metatarsal_5_left';
    case METATARSAL_5_RIGHT = 'metatarsal_5_right';

    // -------------------------------------------------------------------------
    // Lower Limb — Foot (Phalanges)
    // -------------------------------------------------------------------------
    case PROXIMAL_PHALANX_HALLUX_LEFT = 'proximal_phalanx_hallux_left';
    case PROXIMAL_PHALANX_HALLUX_RIGHT = 'proximal_phalanx_hallux_right';
    case DISTAL_PHALANX_HALLUX_LEFT = 'distal_phalanx_hallux_left';
    case DISTAL_PHALANX_HALLUX_RIGHT = 'distal_phalanx_hallux_right';
    case PROXIMAL_PHALANX_2ND_TOE_LEFT = 'proximal_phalanx_2nd_toe_left';
    case PROXIMAL_PHALANX_2ND_TOE_RIGHT = 'proximal_phalanx_2nd_toe_right';
    case MIDDLE_PHALANX_2ND_TOE_LEFT = 'middle_phalanx_2nd_toe_left';
    case MIDDLE_PHALANX_2ND_TOE_RIGHT = 'middle_phalanx_2nd_toe_right';
    case DISTAL_PHALANX_2ND_TOE_LEFT = 'distal_phalanx_2nd_toe_left';
    case DISTAL_PHALANX_2ND_TOE_RIGHT = 'distal_phalanx_2nd_toe_right';
    case PROXIMAL_PHALANX_3RD_TOE_LEFT = 'proximal_phalanx_3rd_toe_left';
    case PROXIMAL_PHALANX_3RD_TOE_RIGHT = 'proximal_phalanx_3rd_toe_right';
    case MIDDLE_PHALANX_3RD_TOE_LEFT = 'middle_phalanx_3rd_toe_left';
    case MIDDLE_PHALANX_3RD_TOE_RIGHT = 'middle_phalanx_3rd_toe_right';
    case DISTAL_PHALANX_3RD_TOE_LEFT = 'distal_phalanx_3rd_toe_left';
    case DISTAL_PHALANX_3RD_TOE_RIGHT = 'distal_phalanx_3rd_toe_right';
    case PROXIMAL_PHALANX_4TH_TOE_LEFT = 'proximal_phalanx_4th_toe_left';
    case PROXIMAL_PHALANX_4TH_TOE_RIGHT = 'proximal_phalanx_4th_toe_right';
    case MIDDLE_PHALANX_4TH_TOE_LEFT = 'middle_phalanx_4th_toe_left';
    case MIDDLE_PHALANX_4TH_TOE_RIGHT = 'middle_phalanx_4th_toe_right';
    case DISTAL_PHALANX_4TH_TOE_LEFT = 'distal_phalanx_4th_toe_left';
    case DISTAL_PHALANX_4TH_TOE_RIGHT = 'distal_phalanx_4th_toe_right';
    case PROXIMAL_PHALANX_5TH_TOE_LEFT = 'proximal_phalanx_5th_toe_left';
    case PROXIMAL_PHALANX_5TH_TOE_RIGHT = 'proximal_phalanx_5th_toe_right';
    case MIDDLE_PHALANX_5TH_TOE_LEFT = 'middle_phalanx_5th_toe_left';
    case MIDDLE_PHALANX_5TH_TOE_RIGHT = 'middle_phalanx_5th_toe_right';
    case DISTAL_PHALANX_5TH_TOE_LEFT = 'distal_phalanx_5th_toe_left';
    case DISTAL_PHALANX_5TH_TOE_RIGHT = 'distal_phalanx_5th_toe_right';

    public function label(): string
    {
        return match ($this) {
            self::FRONTAL => 'Frontal Bone',
            self::PARIETAL_LEFT => 'Parietal Bone (Left)',
            self::PARIETAL_RIGHT => 'Parietal Bone (Right)',
            self::TEMPORAL_LEFT => 'Temporal Bone (Left)',
            self::TEMPORAL_RIGHT => 'Temporal Bone (Right)',
            self::OCCIPITAL => 'Occipital Bone',
            self::SPHENOID => 'Sphenoid Bone',
            self::ETHMOID => 'Ethmoid Bone',
            self::MAXILLA_LEFT => 'Maxilla (Left)',
            self::MAXILLA_RIGHT => 'Maxilla (Right)',
            self::MANDIBLE => 'Mandible',
            self::ZYGOMATIC_LEFT => 'Zygomatic Bone (Left)',
            self::ZYGOMATIC_RIGHT => 'Zygomatic Bone (Right)',
            self::NASAL_LEFT => 'Nasal Bone (Left)',
            self::NASAL_RIGHT => 'Nasal Bone (Right)',
            self::LACRIMAL_LEFT => 'Lacrimal Bone (Left)',
            self::LACRIMAL_RIGHT => 'Lacrimal Bone (Right)',
            self::PALATINE_LEFT => 'Palatine Bone (Left)',
            self::PALATINE_RIGHT => 'Palatine Bone (Right)',
            self::INFERIOR_NASAL_CONCHA_LEFT => 'Inferior Nasal Concha (Left)',
            self::INFERIOR_NASAL_CONCHA_RIGHT => 'Inferior Nasal Concha (Right)',
            self::VOMER => 'Vomer',
            self::MALLEUS_LEFT => 'Malleus (Left)',
            self::MALLEUS_RIGHT => 'Malleus (Right)',
            self::INCUS_LEFT => 'Incus (Left)',
            self::INCUS_RIGHT => 'Incus (Right)',
            self::STAPES_LEFT => 'Stapes (Left)',
            self::STAPES_RIGHT => 'Stapes (Right)',
            self::HYOID => 'Hyoid Bone',
            self::CERVICAL_VERTEBRA_C1_ATLAS => 'C1 — Atlas',
            self::CERVICAL_VERTEBRA_C2_AXIS => 'C2 — Axis',
            self::CERVICAL_VERTEBRA_C3 => 'C3 Cervical Vertebra',
            self::CERVICAL_VERTEBRA_C4 => 'C4 Cervical Vertebra',
            self::CERVICAL_VERTEBRA_C5 => 'C5 Cervical Vertebra',
            self::CERVICAL_VERTEBRA_C6 => 'C6 Cervical Vertebra',
            self::CERVICAL_VERTEBRA_C7 => 'C7 Cervical Vertebra',
            self::THORACIC_VERTEBRA_T1 => 'T1 Thoracic Vertebra',
            self::THORACIC_VERTEBRA_T2 => 'T2 Thoracic Vertebra',
            self::THORACIC_VERTEBRA_T3 => 'T3 Thoracic Vertebra',
            self::THORACIC_VERTEBRA_T4 => 'T4 Thoracic Vertebra',
            self::THORACIC_VERTEBRA_T5 => 'T5 Thoracic Vertebra',
            self::THORACIC_VERTEBRA_T6 => 'T6 Thoracic Vertebra',
            self::THORACIC_VERTEBRA_T7 => 'T7 Thoracic Vertebra',
            self::THORACIC_VERTEBRA_T8 => 'T8 Thoracic Vertebra',
            self::THORACIC_VERTEBRA_T9 => 'T9 Thoracic Vertebra',
            self::THORACIC_VERTEBRA_T10 => 'T10 Thoracic Vertebra',
            self::THORACIC_VERTEBRA_T11 => 'T11 Thoracic Vertebra',
            self::THORACIC_VERTEBRA_T12 => 'T12 Thoracic Vertebra',
            self::LUMBAR_VERTEBRA_L1 => 'L1 Lumbar Vertebra',
            self::LUMBAR_VERTEBRA_L2 => 'L2 Lumbar Vertebra',
            self::LUMBAR_VERTEBRA_L3 => 'L3 Lumbar Vertebra',
            self::LUMBAR_VERTEBRA_L4 => 'L4 Lumbar Vertebra',
            self::LUMBAR_VERTEBRA_L5 => 'L5 Lumbar Vertebra',
            self::SACRUM => 'Sacrum',
            self::COCCYX => 'Coccyx',
            self::STERNUM_MANUBRIUM => 'Sternum — Manubrium',
            self::STERNUM_BODY => 'Sternum — Body',
            self::STERNUM_XIPHOID_PROCESS => 'Sternum — Xiphoid Process',
            self::RIB_1_LEFT => 'Rib 1 (Left)',
            self::RIB_1_RIGHT => 'Rib 1 (Right)',
            self::RIB_2_LEFT => 'Rib 2 (Left)',
            self::RIB_2_RIGHT => 'Rib 2 (Right)',
            self::RIB_3_LEFT => 'Rib 3 (Left)',
            self::RIB_3_RIGHT => 'Rib 3 (Right)',
            self::RIB_4_LEFT => 'Rib 4 (Left)',
            self::RIB_4_RIGHT => 'Rib 4 (Right)',
            self::RIB_5_LEFT => 'Rib 5 (Left)',
            self::RIB_5_RIGHT => 'Rib 5 (Right)',
            self::RIB_6_LEFT => 'Rib 6 (Left)',
            self::RIB_6_RIGHT => 'Rib 6 (Right)',
            self::RIB_7_LEFT => 'Rib 7 (Left)',
            self::RIB_7_RIGHT => 'Rib 7 (Right)',
            self::RIB_8_LEFT => 'Rib 8 (Left)',
            self::RIB_8_RIGHT => 'Rib 8 (Right)',
            self::RIB_9_LEFT => 'Rib 9 (Left)',
            self::RIB_9_RIGHT => 'Rib 9 (Right)',
            self::RIB_10_LEFT => 'Rib 10 (Left)',
            self::RIB_10_RIGHT => 'Rib 10 (Right)',
            self::RIB_11_LEFT => 'Rib 11 (Left) — Floating',
            self::RIB_11_RIGHT => 'Rib 11 (Right) — Floating',
            self::RIB_12_LEFT => 'Rib 12 (Left) — Floating',
            self::RIB_12_RIGHT => 'Rib 12 (Right) — Floating',
            self::CLAVICLE_LEFT => 'Clavicle (Left)',
            self::CLAVICLE_RIGHT => 'Clavicle (Right)',
            self::SCAPULA_LEFT => 'Scapula (Left)',
            self::SCAPULA_RIGHT => 'Scapula (Right)',
            self::HUMERUS_LEFT => 'Humerus (Left)',
            self::HUMERUS_RIGHT => 'Humerus (Right)',
            self::RADIUS_LEFT => 'Radius (Left)',
            self::RADIUS_RIGHT => 'Radius (Right)',
            self::ULNA_LEFT => 'Ulna (Left)',
            self::ULNA_RIGHT => 'Ulna (Right)',
            self::SCAPHOID_LEFT => 'Scaphoid (Left)',
            self::SCAPHOID_RIGHT => 'Scaphoid (Right)',
            self::LUNATE_LEFT => 'Lunate (Left)',
            self::LUNATE_RIGHT => 'Lunate (Right)',
            self::TRIQUETRUM_LEFT => 'Triquetrum (Left)',
            self::TRIQUETRUM_RIGHT => 'Triquetrum (Right)',
            self::PISIFORM_LEFT => 'Pisiform (Left)',
            self::PISIFORM_RIGHT => 'Pisiform (Right)',
            self::TRAPEZIUM_LEFT => 'Trapezium (Left)',
            self::TRAPEZIUM_RIGHT => 'Trapezium (Right)',
            self::TRAPEZOID_LEFT => 'Trapezoid (Left)',
            self::TRAPEZOID_RIGHT => 'Trapezoid (Right)',
            self::CAPITATE_LEFT => 'Capitate (Left)',
            self::CAPITATE_RIGHT => 'Capitate (Right)',
            self::HAMATE_LEFT => 'Hamate (Left)',
            self::HAMATE_RIGHT => 'Hamate (Right)',
            self::METACARPAL_1_LEFT => '1st Metacarpal (Left)',
            self::METACARPAL_1_RIGHT => '1st Metacarpal (Right)',
            self::METACARPAL_2_LEFT => '2nd Metacarpal (Left)',
            self::METACARPAL_2_RIGHT => '2nd Metacarpal (Right)',
            self::METACARPAL_3_LEFT => '3rd Metacarpal (Left)',
            self::METACARPAL_3_RIGHT => '3rd Metacarpal (Right)',
            self::METACARPAL_4_LEFT => '4th Metacarpal (Left)',
            self::METACARPAL_4_RIGHT => '4th Metacarpal (Right)',
            self::METACARPAL_5_LEFT => '5th Metacarpal (Left)',
            self::METACARPAL_5_RIGHT => '5th Metacarpal (Right)',
            self::PROXIMAL_PHALANX_THUMB_LEFT => 'Proximal Phalanx — Thumb (Left)',
            self::PROXIMAL_PHALANX_THUMB_RIGHT => 'Proximal Phalanx — Thumb (Right)',
            self::DISTAL_PHALANX_THUMB_LEFT => 'Distal Phalanx — Thumb (Left)',
            self::DISTAL_PHALANX_THUMB_RIGHT => 'Distal Phalanx — Thumb (Right)',
            self::PROXIMAL_PHALANX_INDEX_LEFT => 'Proximal Phalanx — Index Finger (Left)',
            self::PROXIMAL_PHALANX_INDEX_RIGHT => 'Proximal Phalanx — Index Finger (Right)',
            self::MIDDLE_PHALANX_INDEX_LEFT => 'Middle Phalanx — Index Finger (Left)',
            self::MIDDLE_PHALANX_INDEX_RIGHT => 'Middle Phalanx — Index Finger (Right)',
            self::DISTAL_PHALANX_INDEX_LEFT => 'Distal Phalanx — Index Finger (Left)',
            self::DISTAL_PHALANX_INDEX_RIGHT => 'Distal Phalanx — Index Finger (Right)',
            self::PROXIMAL_PHALANX_MIDDLE_LEFT => 'Proximal Phalanx — Middle Finger (Left)',
            self::PROXIMAL_PHALANX_MIDDLE_RIGHT => 'Proximal Phalanx — Middle Finger (Right)',
            self::MIDDLE_PHALANX_MIDDLE_LEFT => 'Middle Phalanx — Middle Finger (Left)',
            self::MIDDLE_PHALANX_MIDDLE_RIGHT => 'Middle Phalanx — Middle Finger (Right)',
            self::DISTAL_PHALANX_MIDDLE_LEFT => 'Distal Phalanx — Middle Finger (Left)',
            self::DISTAL_PHALANX_MIDDLE_RIGHT => 'Distal Phalanx — Middle Finger (Right)',
            self::PROXIMAL_PHALANX_RING_LEFT => 'Proximal Phalanx — Ring Finger (Left)',
            self::PROXIMAL_PHALANX_RING_RIGHT => 'Proximal Phalanx — Ring Finger (Right)',
            self::MIDDLE_PHALANX_RING_LEFT => 'Middle Phalanx — Ring Finger (Left)',
            self::MIDDLE_PHALANX_RING_RIGHT => 'Middle Phalanx — Ring Finger (Right)',
            self::DISTAL_PHALANX_RING_LEFT => 'Distal Phalanx — Ring Finger (Left)',
            self::DISTAL_PHALANX_RING_RIGHT => 'Distal Phalanx — Ring Finger (Right)',
            self::PROXIMAL_PHALANX_LITTLE_LEFT => 'Proximal Phalanx — Little Finger (Left)',
            self::PROXIMAL_PHALANX_LITTLE_RIGHT => 'Proximal Phalanx — Little Finger (Right)',
            self::MIDDLE_PHALANX_LITTLE_LEFT => 'Middle Phalanx — Little Finger (Left)',
            self::MIDDLE_PHALANX_LITTLE_RIGHT => 'Middle Phalanx — Little Finger (Right)',
            self::DISTAL_PHALANX_LITTLE_LEFT => 'Distal Phalanx — Little Finger (Left)',
            self::DISTAL_PHALANX_LITTLE_RIGHT => 'Distal Phalanx — Little Finger (Right)',
            self::ILIUM_LEFT => 'Ilium (Left)',
            self::ILIUM_RIGHT => 'Ilium (Right)',
            self::ISCHIUM_LEFT => 'Ischium (Left)',
            self::ISCHIUM_RIGHT => 'Ischium (Right)',
            self::PUBIS_LEFT => 'Pubis (Left)',
            self::PUBIS_RIGHT => 'Pubis (Right)',
            self::FEMUR_LEFT => 'Femur (Left)',
            self::FEMUR_RIGHT => 'Femur (Right)',
            self::PATELLA_LEFT => 'Patella (Left)',
            self::PATELLA_RIGHT => 'Patella (Right)',
            self::TIBIA_LEFT => 'Tibia (Left)',
            self::TIBIA_RIGHT => 'Tibia (Right)',
            self::FIBULA_LEFT => 'Fibula (Left)',
            self::FIBULA_RIGHT => 'Fibula (Right)',
            self::CALCANEUS_LEFT => 'Calcaneus (Left)',
            self::CALCANEUS_RIGHT => 'Calcaneus (Right)',
            self::TALUS_LEFT => 'Talus (Left)',
            self::TALUS_RIGHT => 'Talus (Right)',
            self::NAVICULAR_LEFT => 'Navicular (Left)',
            self::NAVICULAR_RIGHT => 'Navicular (Right)',
            self::MEDIAL_CUNEIFORM_LEFT => 'Medial Cuneiform (Left)',
            self::MEDIAL_CUNEIFORM_RIGHT => 'Medial Cuneiform (Right)',
            self::INTERMEDIATE_CUNEIFORM_LEFT => 'Intermediate Cuneiform (Left)',
            self::INTERMEDIATE_CUNEIFORM_RIGHT => 'Intermediate Cuneiform (Right)',
            self::LATERAL_CUNEIFORM_LEFT => 'Lateral Cuneiform (Left)',
            self::LATERAL_CUNEIFORM_RIGHT => 'Lateral Cuneiform (Right)',
            self::CUBOID_LEFT => 'Cuboid (Left)',
            self::CUBOID_RIGHT => 'Cuboid (Right)',
            self::METATARSAL_1_LEFT => '1st Metatarsal (Left)',
            self::METATARSAL_1_RIGHT => '1st Metatarsal (Right)',
            self::METATARSAL_2_LEFT => '2nd Metatarsal (Left)',
            self::METATARSAL_2_RIGHT => '2nd Metatarsal (Right)',
            self::METATARSAL_3_LEFT => '3rd Metatarsal (Left)',
            self::METATARSAL_3_RIGHT => '3rd Metatarsal (Right)',
            self::METATARSAL_4_LEFT => '4th Metatarsal (Left)',
            self::METATARSAL_4_RIGHT => '4th Metatarsal (Right)',
            self::METATARSAL_5_LEFT => '5th Metatarsal (Left)',
            self::METATARSAL_5_RIGHT => '5th Metatarsal (Right)',
            self::PROXIMAL_PHALANX_HALLUX_LEFT => 'Proximal Phalanx — Hallux (Left)',
            self::PROXIMAL_PHALANX_HALLUX_RIGHT => 'Proximal Phalanx — Hallux (Right)',
            self::DISTAL_PHALANX_HALLUX_LEFT => 'Distal Phalanx — Hallux (Left)',
            self::DISTAL_PHALANX_HALLUX_RIGHT => 'Distal Phalanx — Hallux (Right)',
            self::PROXIMAL_PHALANX_2ND_TOE_LEFT => 'Proximal Phalanx — 2nd Toe (Left)',
            self::PROXIMAL_PHALANX_2ND_TOE_RIGHT => 'Proximal Phalanx — 2nd Toe (Right)',
            self::MIDDLE_PHALANX_2ND_TOE_LEFT => 'Middle Phalanx — 2nd Toe (Left)',
            self::MIDDLE_PHALANX_2ND_TOE_RIGHT => 'Middle Phalanx — 2nd Toe (Right)',
            self::DISTAL_PHALANX_2ND_TOE_LEFT => 'Distal Phalanx — 2nd Toe (Left)',
            self::DISTAL_PHALANX_2ND_TOE_RIGHT => 'Distal Phalanx — 2nd Toe (Right)',
            self::PROXIMAL_PHALANX_3RD_TOE_LEFT => 'Proximal Phalanx — 3rd Toe (Left)',
            self::PROXIMAL_PHALANX_3RD_TOE_RIGHT => 'Proximal Phalanx — 3rd Toe (Right)',
            self::MIDDLE_PHALANX_3RD_TOE_LEFT => 'Middle Phalanx — 3rd Toe (Left)',
            self::MIDDLE_PHALANX_3RD_TOE_RIGHT => 'Middle Phalanx — 3rd Toe (Right)',
            self::DISTAL_PHALANX_3RD_TOE_LEFT => 'Distal Phalanx — 3rd Toe (Left)',
            self::DISTAL_PHALANX_3RD_TOE_RIGHT => 'Distal Phalanx — 3rd Toe (Right)',
            self::PROXIMAL_PHALANX_4TH_TOE_LEFT => 'Proximal Phalanx — 4th Toe (Left)',
            self::PROXIMAL_PHALANX_4TH_TOE_RIGHT => 'Proximal Phalanx — 4th Toe (Right)',
            self::MIDDLE_PHALANX_4TH_TOE_LEFT => 'Middle Phalanx — 4th Toe (Left)',
            self::MIDDLE_PHALANX_4TH_TOE_RIGHT => 'Middle Phalanx — 4th Toe (Right)',
            self::DISTAL_PHALANX_4TH_TOE_LEFT => 'Distal Phalanx — 4th Toe (Left)',
            self::DISTAL_PHALANX_4TH_TOE_RIGHT => 'Distal Phalanx — 4th Toe (Right)',
            self::PROXIMAL_PHALANX_5TH_TOE_LEFT => 'Proximal Phalanx — 5th Toe (Left)',
            self::PROXIMAL_PHALANX_5TH_TOE_RIGHT => 'Proximal Phalanx — 5th Toe (Right)',
            self::MIDDLE_PHALANX_5TH_TOE_LEFT => 'Middle Phalanx — 5th Toe (Left)',
            self::MIDDLE_PHALANX_5TH_TOE_RIGHT => 'Middle Phalanx — 5th Toe (Right)',
            self::DISTAL_PHALANX_5TH_TOE_LEFT => 'Distal Phalanx — 5th Toe (Left)',
            self::DISTAL_PHALANX_5TH_TOE_RIGHT => 'Distal Phalanx — 5th Toe (Right)',
        };
    }

    public function category(): string
    {
        return match (true) {
            in_array($this, [
                self::FRONTAL,
                self::PARIETAL_LEFT,
                self::PARIETAL_RIGHT,
                self::TEMPORAL_LEFT,
                self::TEMPORAL_RIGHT,
                self::OCCIPITAL,
                self::SPHENOID,
                self::ETHMOID,
            ], true) => 'Skull — Cranium',

            in_array($this, [
                self::MAXILLA_LEFT,
                self::MAXILLA_RIGHT,
                self::MANDIBLE,
                self::ZYGOMATIC_LEFT,
                self::ZYGOMATIC_RIGHT,
                self::NASAL_LEFT,
                self::NASAL_RIGHT,
                self::LACRIMAL_LEFT,
                self::LACRIMAL_RIGHT,
                self::PALATINE_LEFT,
                self::PALATINE_RIGHT,
                self::INFERIOR_NASAL_CONCHA_LEFT,
                self::INFERIOR_NASAL_CONCHA_RIGHT,
                self::VOMER,
            ], true) => 'Skull — Facial',

            in_array($this, [
                self::MALLEUS_LEFT,
                self::MALLEUS_RIGHT,
                self::INCUS_LEFT,
                self::INCUS_RIGHT,
                self::STAPES_LEFT,
                self::STAPES_RIGHT,
            ], true) => 'Auditory Ossicles',

            $this === self::HYOID => 'Hyoid',

            in_array($this, [
                self::CERVICAL_VERTEBRA_C1_ATLAS,
                self::CERVICAL_VERTEBRA_C2_AXIS,
                self::CERVICAL_VERTEBRA_C3,
                self::CERVICAL_VERTEBRA_C4,
                self::CERVICAL_VERTEBRA_C5,
                self::CERVICAL_VERTEBRA_C6,
                self::CERVICAL_VERTEBRA_C7,
            ], true) => 'Cervical Vertebrae',

            in_array($this, [
                self::THORACIC_VERTEBRA_T1,
                self::THORACIC_VERTEBRA_T2,
                self::THORACIC_VERTEBRA_T3,
                self::THORACIC_VERTEBRA_T4,
                self::THORACIC_VERTEBRA_T5,
                self::THORACIC_VERTEBRA_T6,
                self::THORACIC_VERTEBRA_T7,
                self::THORACIC_VERTEBRA_T8,
                self::THORACIC_VERTEBRA_T9,
                self::THORACIC_VERTEBRA_T10,
                self::THORACIC_VERTEBRA_T11,
                self::THORACIC_VERTEBRA_T12,
            ], true) => 'Thoracic Vertebrae',

            in_array($this, [
                self::LUMBAR_VERTEBRA_L1,
                self::LUMBAR_VERTEBRA_L2,
                self::LUMBAR_VERTEBRA_L3,
                self::LUMBAR_VERTEBRA_L4,
                self::LUMBAR_VERTEBRA_L5,
            ], true) => 'Lumbar Vertebrae',

            in_array($this, [self::SACRUM, self::COCCYX], true) => 'Sacrum and Coccyx',

            in_array($this, [
                self::STERNUM_MANUBRIUM,
                self::STERNUM_BODY,
                self::STERNUM_XIPHOID_PROCESS,
                self::RIB_1_LEFT,
                self::RIB_1_RIGHT,
                self::RIB_2_LEFT,
                self::RIB_2_RIGHT,
                self::RIB_3_LEFT,
                self::RIB_3_RIGHT,
                self::RIB_4_LEFT,
                self::RIB_4_RIGHT,
                self::RIB_5_LEFT,
                self::RIB_5_RIGHT,
                self::RIB_6_LEFT,
                self::RIB_6_RIGHT,
                self::RIB_7_LEFT,
                self::RIB_7_RIGHT,
                self::RIB_8_LEFT,
                self::RIB_8_RIGHT,
                self::RIB_9_LEFT,
                self::RIB_9_RIGHT,
                self::RIB_10_LEFT,
                self::RIB_10_RIGHT,
                self::RIB_11_LEFT,
                self::RIB_11_RIGHT,
                self::RIB_12_LEFT,
                self::RIB_12_RIGHT,
            ], true) => 'Thoracic Cage',

            in_array($this, [
                self::CLAVICLE_LEFT,
                self::CLAVICLE_RIGHT,
                self::SCAPULA_LEFT,
                self::SCAPULA_RIGHT,
            ], true) => 'Pectoral Girdle',

            in_array($this, [
                self::HUMERUS_LEFT,
                self::HUMERUS_RIGHT,
                self::RADIUS_LEFT,
                self::RADIUS_RIGHT,
                self::ULNA_LEFT,
                self::ULNA_RIGHT,
            ], true) => 'Upper Limb — Arm and Forearm',

            in_array($this, [
                self::SCAPHOID_LEFT,
                self::SCAPHOID_RIGHT,
                self::LUNATE_LEFT,
                self::LUNATE_RIGHT,
                self::TRIQUETRUM_LEFT,
                self::TRIQUETRUM_RIGHT,
                self::PISIFORM_LEFT,
                self::PISIFORM_RIGHT,
                self::TRAPEZIUM_LEFT,
                self::TRAPEZIUM_RIGHT,
                self::TRAPEZOID_LEFT,
                self::TRAPEZOID_RIGHT,
                self::CAPITATE_LEFT,
                self::CAPITATE_RIGHT,
                self::HAMATE_LEFT,
                self::HAMATE_RIGHT,
            ], true) => 'Carpals',

            in_array($this, [
                self::METACARPAL_1_LEFT,
                self::METACARPAL_1_RIGHT,
                self::METACARPAL_2_LEFT,
                self::METACARPAL_2_RIGHT,
                self::METACARPAL_3_LEFT,
                self::METACARPAL_3_RIGHT,
                self::METACARPAL_4_LEFT,
                self::METACARPAL_4_RIGHT,
                self::METACARPAL_5_LEFT,
                self::METACARPAL_5_RIGHT,
                self::PROXIMAL_PHALANX_THUMB_LEFT,
                self::PROXIMAL_PHALANX_THUMB_RIGHT,
                self::DISTAL_PHALANX_THUMB_LEFT,
                self::DISTAL_PHALANX_THUMB_RIGHT,
                self::PROXIMAL_PHALANX_INDEX_LEFT,
                self::PROXIMAL_PHALANX_INDEX_RIGHT,
                self::MIDDLE_PHALANX_INDEX_LEFT,
                self::MIDDLE_PHALANX_INDEX_RIGHT,
                self::DISTAL_PHALANX_INDEX_LEFT,
                self::DISTAL_PHALANX_INDEX_RIGHT,
                self::PROXIMAL_PHALANX_MIDDLE_LEFT,
                self::PROXIMAL_PHALANX_MIDDLE_RIGHT,
                self::MIDDLE_PHALANX_MIDDLE_LEFT,
                self::MIDDLE_PHALANX_MIDDLE_RIGHT,
                self::DISTAL_PHALANX_MIDDLE_LEFT,
                self::DISTAL_PHALANX_MIDDLE_RIGHT,
                self::PROXIMAL_PHALANX_RING_LEFT,
                self::PROXIMAL_PHALANX_RING_RIGHT,
                self::MIDDLE_PHALANX_RING_LEFT,
                self::MIDDLE_PHALANX_RING_RIGHT,
                self::DISTAL_PHALANX_RING_LEFT,
                self::DISTAL_PHALANX_RING_RIGHT,
                self::PROXIMAL_PHALANX_LITTLE_LEFT,
                self::PROXIMAL_PHALANX_LITTLE_RIGHT,
                self::MIDDLE_PHALANX_LITTLE_LEFT,
                self::MIDDLE_PHALANX_LITTLE_RIGHT,
                self::DISTAL_PHALANX_LITTLE_LEFT,
                self::DISTAL_PHALANX_LITTLE_RIGHT,
            ], true) => 'Hand',

            in_array($this, [
                self::ILIUM_LEFT,
                self::ILIUM_RIGHT,
                self::ISCHIUM_LEFT,
                self::ISCHIUM_RIGHT,
                self::PUBIS_LEFT,
                self::PUBIS_RIGHT,
            ], true) => 'Pelvic Girdle',

            in_array($this, [
                self::FEMUR_LEFT,
                self::FEMUR_RIGHT,
                self::PATELLA_LEFT,
                self::PATELLA_RIGHT,
                self::TIBIA_LEFT,
                self::TIBIA_RIGHT,
                self::FIBULA_LEFT,
                self::FIBULA_RIGHT,
            ], true) => 'Lower Limb — Thigh and Leg',

            in_array($this, [
                self::CALCANEUS_LEFT,
                self::CALCANEUS_RIGHT,
                self::TALUS_LEFT,
                self::TALUS_RIGHT,
                self::NAVICULAR_LEFT,
                self::NAVICULAR_RIGHT,
                self::MEDIAL_CUNEIFORM_LEFT,
                self::MEDIAL_CUNEIFORM_RIGHT,
                self::INTERMEDIATE_CUNEIFORM_LEFT,
                self::INTERMEDIATE_CUNEIFORM_RIGHT,
                self::LATERAL_CUNEIFORM_LEFT,
                self::LATERAL_CUNEIFORM_RIGHT,
                self::CUBOID_LEFT,
                self::CUBOID_RIGHT,
            ], true) => 'Tarsals',

            default => 'Foot',
        };
    }

    public function isFloatingRib(): bool
    {
        return in_array($this, [
            self::RIB_11_LEFT,
            self::RIB_11_RIGHT,
            self::RIB_12_LEFT,
            self::RIB_12_RIGHT,
        ], true);
    }

    public function isSesamoid(): bool
    {
        return in_array($this, [
            self::PATELLA_LEFT,
            self::PATELLA_RIGHT,
            self::PISIFORM_LEFT,
            self::PISIFORM_RIGHT,
        ], true);
    }

    public function isAuditoryOssicle(): bool
    {
        return in_array($this, [
            self::MALLEUS_LEFT,
            self::MALLEUS_RIGHT,
            self::INCUS_LEFT,
            self::INCUS_RIGHT,
            self::STAPES_LEFT,
            self::STAPES_RIGHT,
        ], true);
    }

    public function isVertebra(): bool
    {
        return in_array($this->category(), [
            'Cervical Vertebrae',
            'Thoracic Vertebrae',
            'Lumbar Vertebrae',
            'Sacrum and Coccyx',
        ], true);
    }

    public function isLongBone(): bool
    {
        return in_array($this, [
            self::HUMERUS_LEFT,
            self::HUMERUS_RIGHT,
            self::RADIUS_LEFT,
            self::RADIUS_RIGHT,
            self::ULNA_LEFT,
            self::ULNA_RIGHT,
            self::FEMUR_LEFT,
            self::FEMUR_RIGHT,
            self::TIBIA_LEFT,
            self::TIBIA_RIGHT,
            self::FIBULA_LEFT,
            self::FIBULA_RIGHT,
            self::METACARPAL_1_LEFT,
            self::METACARPAL_1_RIGHT,
            self::METACARPAL_2_LEFT,
            self::METACARPAL_2_RIGHT,
            self::METACARPAL_3_LEFT,
            self::METACARPAL_3_RIGHT,
            self::METACARPAL_4_LEFT,
            self::METACARPAL_4_RIGHT,
            self::METACARPAL_5_LEFT,
            self::METACARPAL_5_RIGHT,
            self::METATARSAL_1_LEFT,
            self::METATARSAL_1_RIGHT,
            self::METATARSAL_2_LEFT,
            self::METATARSAL_2_RIGHT,
            self::METATARSAL_3_LEFT,
            self::METATARSAL_3_RIGHT,
            self::METATARSAL_4_LEFT,
            self::METATARSAL_4_RIGHT,
            self::METATARSAL_5_LEFT,
            self::METATARSAL_5_RIGHT,
        ], true);
    }

    public function isLeftSide(): bool
    {
        return str_ends_with($this->value, '_left');
    }

    public function isRightSide(): bool
    {
        return str_ends_with($this->value, '_right');
    }

    public function isMedial(): bool
    {
        return !$this->isLeftSide() && !$this->isRightSide();
    }

    /** @return list<self> */
    public static function byCategory(string $category): array
    {
        return array_values(
            array_filter(self::cases(), fn(self $case) => $case->category() === $category)
        );
    }

    /** @return list<self> */
    public static function longBones(): array
    {
        return array_values(array_filter(self::cases(), fn(self $case) => $case->isLongBone()));
    }

    /** @return list<self> */
    public static function vertebrae(): array
    {
        return array_values(array_filter(self::cases(), fn(self $case) => $case->isVertebra()));
    }
}
