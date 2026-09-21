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

/**
 * This enum represents various body parts
 */
enum BodyPart: string
{
    // -------------------------------------------------------------------------
    // Head - Skull
    // -------------------------------------------------------------------------
    case SCALP = 'scalp';
    case CRANIUM = 'cranium';
    case FRONTAL_BONE = 'frontal_bone';
    case PARIETAL_BONE = 'parietal_bone';
    case TEMPORAL_BONE = 'temporal_bone';
    case OCCIPITAL_BONE = 'occipital_bone';
    case SPHENOID_BONE = 'sphenoid_bone';
    case ETHMOID_BONE = 'ethmoid_bone';

    // -------------------------------------------------------------------------
    // Brain
    // -------------------------------------------------------------------------
    case CEREBRUM = 'cerebrum';
    case CEREBELLUM = 'cerebellum';
    case BRAINSTEM = 'brainstem';
    case MIDBRAIN = 'midbrain';
    case PONS = 'pons';
    case MEDULLA_OBLONGATA = 'medulla_oblongata';
    case THALAMUS = 'thalamus';
    case HYPOTHALAMUS = 'hypothalamus';
    case PITUITARY_GLAND = 'pituitary_gland';
    case PINEAL_GLAND = 'pineal_gland';
    case CORPUS_CALLOSUM = 'corpus_callosum';
    case AMYGDALA = 'amygdala';
    case HIPPOCAMPUS = 'hippocampus';
    case BASAL_GANGLIA = 'basal_ganglia';

    // -------------------------------------------------------------------------
    // Meninges
    // -------------------------------------------------------------------------
    case DURA_MATER = 'dura_mater';
    case ARACHNOID_MATER = 'arachnoid_mater';
    case PIA_MATER = 'pia_mater';

    // -------------------------------------------------------------------------
    // Face
    // -------------------------------------------------------------------------
    case ORBIT = 'orbit';
    case MAXILLA = 'maxilla';
    case MANDIBLE = 'mandible';
    case ZYGOMATIC_BONE = 'zygomatic_bone';
    case NASAL_BONE = 'nasal_bone';
    case TEMPOROMANDIBULAR_JOINT = 'temporomandibular_joint';

    // -------------------------------------------------------------------------
    // Eye
    // -------------------------------------------------------------------------
    case CORNEA = 'cornea';
    case IRIS = 'iris';
    case PUPIL = 'pupil';
    case LENS = 'lens';
    case RETINA = 'retina';
    case VITREOUS_HUMOR = 'vitreous_humor';
    case AQUEOUS_HUMOR = 'aqueous_humor';
    case OPTIC_NERVE = 'optic_nerve';
    case CONJUNCTIVA = 'conjunctiva';
    case SCLERA = 'sclera';
    case CHOROID = 'choroid';

    // -------------------------------------------------------------------------
    // Ear
    // -------------------------------------------------------------------------
    case AURICLE = 'auricle';
    case EXTERNAL_AUDITORY_CANAL = 'external_auditory_canal';
    case TYMPANIC_MEMBRANE = 'tympanic_membrane';
    case MALLEUS = 'malleus';
    case INCUS = 'incus';
    case STAPES = 'stapes';
    case COCHLEA = 'cochlea';
    case VESTIBULE = 'vestibule';
    case SEMICIRCULAR_CANALS = 'semicircular_canals';
    case EUSTACHIAN_TUBE = 'eustachian_tube';

    // -------------------------------------------------------------------------
    // Nose
    // -------------------------------------------------------------------------
    case NASAL_CAVITY = 'nasal_cavity';
    case NASAL_SEPTUM = 'nasal_septum';
    case SUPERIOR_NASAL_CONCHA = 'superior_nasal_concha';
    case MIDDLE_NASAL_CONCHA = 'middle_nasal_concha';
    case INFERIOR_NASAL_CONCHA = 'inferior_nasal_concha';
    case OLFACTORY_EPITHELIUM = 'olfactory_epithelium';
    case PARANASAL_SINUSES = 'paranasal_sinuses';

    // -------------------------------------------------------------------------
    // Mouth
    // -------------------------------------------------------------------------
    case HARD_PALATE = 'hard_palate';
    case SOFT_PALATE = 'soft_palate';
    case UVULA = 'uvula';
    case TONGUE = 'tongue';
    case TEETH = 'teeth';
    case PAROTID_GLAND = 'parotid_gland';
    case SUBMANDIBULAR_GLAND = 'submandibular_gland';
    case SUBLINGUAL_GLAND = 'sublingual_gland';
    case TONSILS = 'tonsils';
    case BUCCAL_MUCOSA = 'buccal_mucosa';

    // -------------------------------------------------------------------------
    // Pharynx
    // -------------------------------------------------------------------------
    case NASOPHARYNX = 'nasopharynx';
    case OROPHARYNX = 'oropharynx';
    case HYPOPHARYNX = 'hypopharynx';

    // -------------------------------------------------------------------------
    // Neck & Larynx
    // -------------------------------------------------------------------------
    case HYOID_BONE = 'hyoid_bone';
    case EPIGLOTTIS = 'epiglottis';
    case ARY_EPIGLOTTIC_FOLDS = 'ary_epiglottic_folds';
    case CRICO_ARYTENOID_JOINT = 'crico_arytenoid_joint';
    case PARAGLOTTIC_SPACE = 'paraglottic_space';
    case FALSE_VOCAL_CORDS = 'false_vocal_cords';
    case LARYNGEAL_VENTRICLE = 'laryngeal_ventricle';
    case TRANS_GLOTTIC = 'trans_glottic';
    case PRE_EPIGLOTTIS = 'pre_epiglottis';
    case SUPRAGLOTTIS = 'supraglottis';
    case GLOTTIS = 'glottis';
    case SUBGLOTTIS = 'subglottis';
    case VOCAL_CORD = 'vocal_cord';
    case LARYNX = 'larynx';
    case THYROID_CARTILAGE = 'thyroid_cartilage';
    case CRICOID_CARTILAGE = 'cricoid_cartilage';
    case ARYTENOID_CARTILAGE = 'arytenoid_cartilage';
    case CORNICULATE_CARTILAGE = 'corniculate_cartilage';
    case CUNEIFORM_CARTILAGE = 'cuneiform_cartilage';
    case TRACHEA = 'trachea';
    case THYROID_GLAND = 'thyroid_gland';
    case PARATHYROID_GLAND = 'parathyroid_gland';
    case COMMON_CAROTID_ARTERY = 'common_carotid_artery';
    case INTERNAL_JUGULAR_VEIN = 'internal_jugular_vein';
    case CERVICAL_LYMPH_NODE = 'cervical_lymph_node';
    case CERVICAL_VERTEBRAE = 'cervical_vertebrae';

    // -------------------------------------------------------------------------
    // Thorax
    // -------------------------------------------------------------------------
    case STERNUM = 'sternum';
    case RIBS = 'ribs';
    case CLAVICLE = 'clavicle';
    case SCAPULA = 'scapula';
    case THORACIC_VERTEBRAE = 'thoracic_vertebrae';
    case ESOPHAGUS = 'esophagus';
    case DIAPHRAGM = 'diaphragm';
    case THYMUS = 'thymus';

    // -------------------------------------------------------------------------
    // Lungs
    // -------------------------------------------------------------------------
    case RIGHT_LUNG = 'right_lung';
    case LEFT_LUNG = 'left_lung';
    case BRONCHI = 'bronchi';
    case BRONCHIOLES = 'bronchioles';
    case ALVEOLI = 'alveoli';
    case PLEURA = 'pleura';

    // -------------------------------------------------------------------------
    // Heart & Great Vessels
    // -------------------------------------------------------------------------
    case HEART = 'heart';
    case RIGHT_ATRIUM = 'right_atrium';
    case LEFT_ATRIUM = 'left_atrium';
    case RIGHT_VENTRICLE = 'right_ventricle';
    case LEFT_VENTRICLE = 'left_ventricle';
    case AORTA = 'aorta';
    case PULMONARY_ARTERY = 'pulmonary_artery';
    case PULMONARY_VEIN = 'pulmonary_vein';
    case SUPERIOR_VENA_CAVA = 'superior_vena_cava';
    case INFERIOR_VENA_CAVA = 'inferior_vena_cava';
    case CORONARY_ARTERY = 'coronary_artery';
    case TRICUSPID_VALVE = 'tricuspid_valve';
    case MITRAL_VALVE = 'mitral_valve';
    case AORTIC_VALVE = 'aortic_valve';
    case PULMONARY_VALVE = 'pulmonary_valve';
    case PERICARDIUM = 'pericardium';

    // -------------------------------------------------------------------------
    // Abdomen
    // -------------------------------------------------------------------------
    case STOMACH = 'stomach';
    case DUODENUM = 'duodenum';
    case JEJUNUM = 'jejunum';
    case ILEUM = 'ileum';
    case CECUM = 'cecum';
    case APPENDIX = 'appendix';
    case ASCENDING_COLON = 'ascending_colon';
    case TRANSVERSE_COLON = 'transverse_colon';
    case DESCENDING_COLON = 'descending_colon';
    case SIGMOID_COLON = 'sigmoid_colon';
    case RECTUM = 'rectum';
    case ANUS = 'anus';
    case LIVER = 'liver';
    case GALLBLADDER = 'gallbladder';
    case BILE_DUCT = 'bile_duct';
    case PANCREAS = 'pancreas';
    case SPLEEN = 'spleen';
    case RIGHT_KIDNEY = 'right_kidney';
    case LEFT_KIDNEY = 'left_kidney';
    case RIGHT_ADRENAL_GLAND = 'right_adrenal_gland';
    case LEFT_ADRENAL_GLAND = 'left_adrenal_gland';
    case URETER = 'ureter';
    case PERITONEUM = 'peritoneum';
    case OMENTUM = 'omentum';
    case ABDOMINAL_AORTA = 'abdominal_aorta';
    case PORTAL_VEIN = 'portal_vein';
    case LUMBAR_VERTEBRAE = 'lumbar_vertebrae';

    // -------------------------------------------------------------------------
    // Pelvis
    // -------------------------------------------------------------------------
    case URINARY_BLADDER = 'urinary_bladder';
    case URETHRA = 'urethra';
    case SACRUM = 'sacrum';
    case COCCYX = 'coccyx';
    case ILIUM = 'ilium';
    case ISCHIUM = 'ischium';
    case PUBIS = 'pubis';
    case SACROILIAC_JOINT = 'sacroiliac_joint';
    case PUBIC_SYMPHYSIS = 'pubic_symphysis';

    // -------------------------------------------------------------------------
    // Female Reproductive
    // -------------------------------------------------------------------------
    case UTERUS = 'uterus';
    case CERVIX = 'cervix';
    case FALLOPIAN_TUBE = 'fallopian_tube';
    case OVARY = 'ovary';
    case VAGINA = 'vagina';

    // -------------------------------------------------------------------------
    // Male Reproductive
    // -------------------------------------------------------------------------
    case PROSTATE = 'prostate';
    case SEMINAL_VESICLE = 'seminal_vesicle';
    case VAS_DEFERENS = 'vas_deferens';
    case TESTIS = 'testis';
    case EPIDIDYMIS = 'epididymis';
    case PENIS = 'penis';
    case SCROTUM = 'scrotum';

    // -------------------------------------------------------------------------
    // Upper Limb
    // -------------------------------------------------------------------------
    case SHOULDER_JOINT = 'shoulder_joint';
    case HUMERUS = 'humerus';
    case ELBOW_JOINT = 'elbow_joint';
    case RADIUS = 'radius';
    case ULNA = 'ulna';
    case WRIST_JOINT = 'wrist_joint';
    case SCAPHOID = 'scaphoid';
    case LUNATE = 'lunate';
    case TRIQUETRUM = 'triquetrum';
    case PISIFORM = 'pisiform';
    case TRAPEZIUM = 'trapezium';
    case TRAPEZOID_BONE = 'trapezoid_bone';
    case CAPITATE = 'capitate';
    case HAMATE = 'hamate';
    case METACARPALS = 'metacarpals';
    case PHALANGES_HAND = 'phalanges_hand';

    // -------------------------------------------------------------------------
    // Lower Limb
    // -------------------------------------------------------------------------
    case HIP_JOINT = 'hip_joint';
    case FEMUR = 'femur';
    case PATELLA = 'patella';
    case KNEE_JOINT = 'knee_joint';
    case MEDIAL_MENISCUS = 'medial_meniscus';
    case LATERAL_MENISCUS = 'lateral_meniscus';
    case ANTERIOR_CRUCIATE_LIGAMENT = 'anterior_cruciate_ligament';
    case POSTERIOR_CRUCIATE_LIGAMENT = 'posterior_cruciate_ligament';
    case TIBIA = 'tibia';
    case FIBULA = 'fibula';
    case ANKLE_JOINT = 'ankle_joint';
    case TALUS = 'talus';
    case CALCANEUS = 'calcaneus';
    case NAVICULAR_BONE = 'navicular_bone';
    case CUBOID = 'cuboid';
    case CUNEIFORM_BONES = 'cuneiform_bones';
    case METATARSALS = 'metatarsals';
    case PHALANGES_FOOT = 'phalanges_foot';

    // OTHERS
    case PYRIFORM_SINUSES = 'pyriform_sinuses';
    case INFRAHYOID_MUSCLES = 'infrahyoid_muscles';
    case VALLECULAE = 'valleculae';
    case SUPERIOR_LONGITUDINAL_FASCICULUS = 'superior_longitudinal_fasciculus';
    case PALATOPHARYNGEUS = 'palatopharyngeus';
    case PALATOGLOSSUS = 'palatoglossus';
    case JUVENILE_NASOPHARYNGEAL_ANGIOFIBROMA = 'juvenile_nasopharyngeal_angiofibroma';
    case SUPERIOR_CORNUA = 'superior_cornua';
    case INFERIOR_CORNUA = 'inferior_cornua';
    case MYLOHYOID = 'mylohyoid';
    case GENIOHYOID = 'geniohyoid';
    case SPHENOPALATINE_FORAMEN = 'sphenopalatine_foramen';
    case NASOPHARYNGEAL_CARCINOMA = 'nasopharyngeal_carcinoma';
    case EUSTACHIAN_TUBE_ORIFICE = 'eustachian_tube_orifice';
    case STERNOCLEIDOMASTOID = 'sternocleidomastoid';
    case PHARYNGEAL_MUCOSAL_SPACE = 'pharyngeal_mucosal_space';
    case PARAPHARYNGEAL_SPACE = 'parapharyngeal_space';
    case CAROTID_SPACE = 'carotid_space';
    case PTERYGOPALATINE_FOSSA = 'pterygopalatine_fossa';
    case RETROMAXILLARY_FAT_PAD = 'retromaxillary_fat_pad';
    case LATERAL_PTERYGOID_PLATE = 'lateral_pterygoid_plate';
    case MASTICATOR_SPACE_INFRATEMPORAL_FOSSA = 'masticator_space_infratemporal_fossa';
    case PTERYGOID_VENOUS_PLEXUS = 'pterygoid_venous_plexus';
    case SPHENOPALATINE_BLOCK = 'sphenopalatine_block';
    case RETROMANDIBULAR_VEIN = 'retromandibular_vein';

    /**
     * This method returns a human-readable label for each body part, which can be used in user interfaces, reports, or any context where a more descriptive name is preferred over the enum value.
     */
    public function label(): string
    {
        return match ($this) {
            self::MYLOHYOID => 'Mylohyoid',
            self::GENIOHYOID => 'Geniohyoid',
            self::INFERIOR_CORNUA => 'Inferior cornua',
            self::PALATOGLOSSUS => 'Palatoglossus',
            self::PALATOPHARYNGEUS => 'Palatopharyngeus',
            self::SUPERIOR_CORNUA => 'Superior cornua',
            self::SPHENOPALATINE_FORAMEN => 'Sphenopalatine foramen',
            self::NASOPHARYNGEAL_CARCINOMA => 'Nasopharyngeal carcinoma',
            self::PHARYNGEAL_MUCOSAL_SPACE => 'Pharyngeal mucosal space',
            self::STERNOCLEIDOMASTOID => 'Sternocleidomastoid',
            self::EUSTACHIAN_TUBE_ORIFICE => 'Eustachian tube orifice',
            self::CAROTID_SPACE => 'Carotid space',
            self::PARAPHARYNGEAL_SPACE => 'Parapharyngeal space',
            self::VALLECULAE => 'Valleculae',
            self::INFRAHYOID_MUSCLES => 'Infrahyoid muscles',
            self::PYRIFORM_SINUSES => 'Pyriform sinuses',
            self::SUPERIOR_LONGITUDINAL_FASCICULUS => 'Superior longitudinal fasciculus',
            self::JUVENILE_NASOPHARYNGEAL_ANGIOFIBROMA => 'Juvenile nasopharyngeal angiofibroma(JNA)',
            self::PTERYGOPALATINE_FOSSA => 'Pterygopalatine fossa',
            self::RETROMAXILLARY_FAT_PAD => 'Retromaxillary fat pad',
            self::LATERAL_PTERYGOID_PLATE => 'Lateral pterygoid plate',
            self::RETROMANDIBULAR_VEIN => 'Retromandibular vein',
            self::MASTICATOR_SPACE_INFRATEMPORAL_FOSSA => 'Masticator space infratemporal fossa',
            self::PTERYGOID_VENOUS_PLEXUS => 'Pterygoid venous plexus',
            self::SPHENOPALATINE_BLOCK => 'Sphenopalatine block',
            self::SCALP => 'Scalp',
            self::CRANIUM => 'Cranium (Skull)',
            self::FRONTAL_BONE => 'Frontal Bone',
            self::PARIETAL_BONE => 'Parietal Bone',
            self::TEMPORAL_BONE => 'Temporal Bone',
            self::OCCIPITAL_BONE => 'Occipital Bone',
            self::SPHENOID_BONE => 'Sphenoid Bone',
            self::ETHMOID_BONE => 'Ethmoid Bone',
            self::CEREBRUM => 'Cerebrum',
            self::CEREBELLUM => 'Cerebellum',
            self::BRAINSTEM => 'Brainstem',
            self::MIDBRAIN => 'Midbrain (Mesencephalon)',
            self::PONS => 'Pons',
            self::MEDULLA_OBLONGATA => 'Medulla Oblongata',
            self::THALAMUS => 'Thalamus',
            self::HYPOTHALAMUS => 'Hypothalamus',
            self::PITUITARY_GLAND => 'Pituitary Gland (Hypophysis)',
            self::PINEAL_GLAND => 'Pineal Gland',
            self::CORPUS_CALLOSUM => 'Corpus Callosum',
            self::AMYGDALA => 'Amygdala',
            self::HIPPOCAMPUS => 'Hippocampus',
            self::BASAL_GANGLIA => 'Basal Ganglia',
            self::DURA_MATER => 'Dura Mater',
            self::ARACHNOID_MATER => 'Arachnoid Mater',
            self::PIA_MATER => 'Pia Mater',
            self::ORBIT => 'Orbit (Eye Socket)',
            self::MAXILLA => 'Maxilla (Upper Jaw)',
            self::MANDIBLE => 'Mandible (Lower Jaw)',
            self::ZYGOMATIC_BONE => 'Zygomatic Bone (Cheekbone)',
            self::NASAL_BONE => 'Nasal Bone',
            self::TEMPOROMANDIBULAR_JOINT => 'Temporomandibular Joint (TMJ)',
            self::CORNEA => 'Cornea',
            self::IRIS => 'Iris',
            self::PUPIL => 'Pupil',
            self::LENS => 'Lens',
            self::RETINA => 'Retina',
            self::VITREOUS_HUMOR => 'Vitreous Humor',
            self::AQUEOUS_HUMOR => 'Aqueous Humor',
            self::OPTIC_NERVE => 'Optic Nerve (CN II)',
            self::CONJUNCTIVA => 'Conjunctiva',
            self::SCLERA => 'Sclera',
            self::CHOROID => 'Choroid',
            self::AURICLE => 'Auricle (Pinna)',
            self::EXTERNAL_AUDITORY_CANAL => 'External Auditory Canal',
            self::TYMPANIC_MEMBRANE => 'Tympanic Membrane (Eardrum)',
            self::MALLEUS => 'Malleus (Hammer)',
            self::INCUS => 'Incus (Anvil)',
            self::STAPES => 'Stapes (Stirrup)',
            self::COCHLEA => 'Cochlea',
            self::VESTIBULE => 'Vestibule',
            self::SEMICIRCULAR_CANALS => 'Semicircular Canals',
            self::EUSTACHIAN_TUBE => 'Eustachian Tube',
            self::NASAL_CAVITY => 'Nasal Cavity',
            self::NASAL_SEPTUM => 'Nasal Septum',
            self::SUPERIOR_NASAL_CONCHA => 'Superior Nasal Concha (Superior Turbinate)',
            self::MIDDLE_NASAL_CONCHA => 'Middle Nasal Concha (Middle Turbinate)',
            self::INFERIOR_NASAL_CONCHA => 'Inferior Nasal Concha (Inferior Turbinate)',
            self::OLFACTORY_EPITHELIUM => 'Olfactory Epithelium',
            self::PARANASAL_SINUSES => 'Paranasal Sinuses',
            self::HARD_PALATE => 'Hard Palate',
            self::SOFT_PALATE => 'Soft Palate',
            self::UVULA => 'Uvula',
            self::TONGUE => 'Tongue',
            self::TEETH => 'Teeth',
            self::PAROTID_GLAND => 'Parotid Gland',
            self::SUBMANDIBULAR_GLAND => 'Submandibular Gland',
            self::SUBLINGUAL_GLAND => 'Sublingual Gland',
            self::TONSILS => 'Palatine Tonsils',
            self::BUCCAL_MUCOSA => 'Buccal Mucosa',
            self::NASOPHARYNX => 'Nasopharynx',
            self::OROPHARYNX => 'Oropharynx',
            self::HYPOPHARYNX => 'Hypopharynx (Laryngopharynx)',
            self::HYOID_BONE => 'Hyoid Bone',
            self::EPIGLOTTIS => 'Epiglottis',
            self::PRE_EPIGLOTTIS => 'Pre-Epiglottic Space',
            self::SUPRAGLOTTIS => 'Supraglottis',
            self::GLOTTIS => 'Glottis',
            self::SUBGLOTTIS => 'Subglottis',
            self::VOCAL_CORD => 'Vocal Cord (Vocal Fold)',
            self::LARYNX => 'Larynx',
            self::THYROID_CARTILAGE => 'Thyroid Cartilage',
            self::CRICOID_CARTILAGE => 'Cricoid Cartilage',
            self::ARYTENOID_CARTILAGE => 'Arytenoid Cartilage',
            self::CORNICULATE_CARTILAGE => 'Corniculate Cartilage',
            self::CUNEIFORM_CARTILAGE => 'Cuneiform Cartilage',
            self::TRACHEA => 'Trachea',
            self::THYROID_GLAND => 'Thyroid Gland',
            self::PARATHYROID_GLAND => 'Parathyroid Gland',
            self::COMMON_CAROTID_ARTERY => 'Common Carotid Artery',
            self::INTERNAL_JUGULAR_VEIN => 'Internal Jugular Vein',
            self::CERVICAL_LYMPH_NODE => 'Cervical Lymph Node',
            self::CERVICAL_VERTEBRAE => 'Cervical Vertebrae (C1–C7)',
            self::STERNUM => 'Sternum',
            self::RIBS => 'Ribs (1–12)',
            self::CLAVICLE => 'Clavicle (Collarbone)',
            self::SCAPULA => 'Scapula (Shoulder Blade)',
            self::THORACIC_VERTEBRAE => 'Thoracic Vertebrae (T1–T12)',
            self::ESOPHAGUS => 'Esophagus',
            self::DIAPHRAGM => 'Diaphragm',
            self::THYMUS => 'Thymus',
            self::RIGHT_LUNG => 'Right Lung (3 lobes)',
            self::LEFT_LUNG => 'Left Lung (2 lobes)',
            self::BRONCHI => 'Bronchi',
            self::BRONCHIOLES => 'Bronchioles',
            self::ALVEOLI => 'Alveoli',
            self::PLEURA => 'Pleura',
            self::HEART => 'Heart',
            self::RIGHT_ATRIUM => 'Right Atrium',
            self::LEFT_ATRIUM => 'Left Atrium',
            self::RIGHT_VENTRICLE => 'Right Ventricle',
            self::LEFT_VENTRICLE => 'Left Ventricle',
            self::AORTA => 'Aorta',
            self::PULMONARY_ARTERY => 'Pulmonary Artery',
            self::PULMONARY_VEIN => 'Pulmonary Vein',
            self::SUPERIOR_VENA_CAVA => 'Superior Vena Cava',
            self::INFERIOR_VENA_CAVA => 'Inferior Vena Cava',
            self::CORONARY_ARTERY => 'Coronary Artery',
            self::TRICUSPID_VALVE => 'Tricuspid Valve',
            self::MITRAL_VALVE => 'Mitral Valve (Bicuspid Valve)',
            self::AORTIC_VALVE => 'Aortic Valve',
            self::PULMONARY_VALVE => 'Pulmonary Valve',
            self::PERICARDIUM => 'Pericardium',
            self::STOMACH => 'Stomach',
            self::DUODENUM => 'Duodenum',
            self::JEJUNUM => 'Jejunum',
            self::ILEUM => 'Ileum',
            self::CECUM => 'Cecum',
            self::APPENDIX => 'Vermiform Appendix',
            self::ASCENDING_COLON => 'Ascending Colon',
            self::TRANSVERSE_COLON => 'Transverse Colon',
            self::DESCENDING_COLON => 'Descending Colon',
            self::SIGMOID_COLON => 'Sigmoid Colon',
            self::RECTUM => 'Rectum',
            self::ANUS => 'Anus',
            self::LIVER => 'Liver',
            self::GALLBLADDER => 'Gallbladder',
            self::BILE_DUCT => 'Common Bile Duct',
            self::PANCREAS => 'Pancreas',
            self::SPLEEN => 'Spleen',
            self::RIGHT_KIDNEY => 'Right Kidney',
            self::LEFT_KIDNEY => 'Left Kidney',
            self::RIGHT_ADRENAL_GLAND => 'Right Adrenal Gland (Suprarenal)',
            self::LEFT_ADRENAL_GLAND => 'Left Adrenal Gland (Suprarenal)',
            self::URETER => 'Ureter',
            self::PERITONEUM => 'Peritoneum',
            self::OMENTUM => 'Omentum',
            self::ABDOMINAL_AORTA => 'Abdominal Aorta',
            self::PORTAL_VEIN => 'Portal Vein',
            self::LUMBAR_VERTEBRAE => 'Lumbar Vertebrae (L1–L5)',
            self::URINARY_BLADDER => 'Urinary Bladder',
            self::URETHRA => 'Urethra',
            self::SACRUM => 'Sacrum',
            self::COCCYX => 'Coccyx (Tailbone)',
            self::ILIUM => 'Ilium',
            self::ISCHIUM => 'Ischium',
            self::PUBIS => 'Pubis',
            self::SACROILIAC_JOINT => 'Sacroiliac Joint',
            self::PUBIC_SYMPHYSIS => 'Pubic Symphysis',
            self::UTERUS => 'Uterus',
            self::CERVIX => 'Cervix',
            self::FALLOPIAN_TUBE => 'Fallopian Tube',
            self::OVARY => 'Ovary',
            self::VAGINA => 'Vagina',
            self::PROSTATE => 'Prostate Gland',
            self::SEMINAL_VESICLE => 'Seminal Vesicle',
            self::VAS_DEFERENS => 'Vas Deferens',
            self::TESTIS => 'Testis',
            self::EPIDIDYMIS => 'Epididymis',
            self::PENIS => 'Penis',
            self::SCROTUM => 'Scrotum',
            self::SHOULDER_JOINT => 'Shoulder Joint (Glenohumeral Joint)',
            self::HUMERUS => 'Humerus',
            self::ELBOW_JOINT => 'Elbow Joint',
            self::RADIUS => 'Radius',
            self::ULNA => 'Ulna',
            self::WRIST_JOINT => 'Wrist Joint (Radiocarpal Joint)',
            self::SCAPHOID => 'Scaphoid',
            self::LUNATE => 'Lunate',
            self::TRIQUETRUM => 'Triquetrum',
            self::PISIFORM => 'Pisiform',
            self::TRAPEZIUM => 'Trapezium',
            self::TRAPEZOID_BONE => 'Trapezoid',
            self::CAPITATE => 'Capitate',
            self::HAMATE => 'Hamate',
            self::METACARPALS => 'Metacarpals',
            self::PHALANGES_HAND => 'Phalanges (Hand)',
            self::HIP_JOINT => 'Hip Joint (Coxofemoral Joint)',
            self::FEMUR => 'Femur (Thigh Bone)',
            self::PATELLA => 'Patella (Kneecap)',
            self::KNEE_JOINT => 'Knee Joint',
            self::MEDIAL_MENISCUS => 'Medial Meniscus',
            self::LATERAL_MENISCUS => 'Lateral Meniscus',
            self::ANTERIOR_CRUCIATE_LIGAMENT => 'Anterior Cruciate Ligament (ACL)',
            self::POSTERIOR_CRUCIATE_LIGAMENT => 'Posterior Cruciate Ligament (PCL)',
            self::TIBIA => 'Tibia (Shin Bone)',
            self::FIBULA => 'Fibula',
            self::ANKLE_JOINT => 'Ankle Joint (Talocrural Joint)',
            self::TALUS => 'Talus',
            self::CALCANEUS => 'Calcaneus (Heel Bone)',
            self::NAVICULAR_BONE => 'Navicular Bone',
            self::CUBOID => 'Cuboid',
            self::CUNEIFORM_BONES => 'Cuneiform Bones',
            self::METATARSALS => 'Metatarsals',
            self::PHALANGES_FOOT => 'Phalanges (Foot)',
        };
    }

    /**
     * Bones are defined as rigid connective tissues that form the skeleton of the body, providing support, protection for internal organs, and facilitating movement. 
     * They can be classified into different types based on their shape and function, such as long bones (e.g., femur), short bones (e.g., carpals), flat bones (e.g., sternum), and irregular bones (e.g., vertebrae).
     */
    public function isBone(): bool
    {
        return in_array($this, [
            self::CRANIUM,
            self::FRONTAL_BONE,
            self::PARIETAL_BONE,
            self::TEMPORAL_BONE,
            self::OCCIPITAL_BONE,
            self::SPHENOID_BONE,
            self::ETHMOID_BONE,
            self::MAXILLA,
            self::MANDIBLE,
            self::ZYGOMATIC_BONE,
            self::NASAL_BONE,
            self::MALLEUS,
            self::INCUS,
            self::STAPES,
            self::HYOID_BONE,
            self::CERVICAL_VERTEBRAE,
            self::THORACIC_VERTEBRAE,
            self::LUMBAR_VERTEBRAE,
            self::SACRUM,
            self::COCCYX,
            self::STERNUM,
            self::RIBS,
            self::CLAVICLE,
            self::SCAPULA,
            self::HUMERUS,
            self::RADIUS,
            self::ULNA,
            self::SCAPHOID,
            self::LUNATE,
            self::TRIQUETRUM,
            self::PISIFORM,
            self::TRAPEZIUM,
            self::TRAPEZOID_BONE,
            self::CAPITATE,
            self::HAMATE,
            self::METACARPALS,
            self::PHALANGES_HAND,
            self::ILIUM,
            self::ISCHIUM,
            self::PUBIS,
            self::FEMUR,
            self::PATELLA,
            self::TIBIA,
            self::FIBULA,
            self::TALUS,
            self::CALCANEUS,
            self::NAVICULAR_BONE,
            self::CUBOID,
            self::CUNEIFORM_BONES,
            self::METATARSALS,
            self::PHALANGES_FOOT,
        ]);
    }

    /**
     * Cartilage is defined as a flexible connective tissue that provides support and cushioning to various parts of the body, particularly in joints and the respiratory system. 
     * It can be classified into three main types: hyaline cartilage (found in the nose, trachea, and at the ends of long bones), fibrocartilage (found in intervertebral discs and menisci), and elastic cartilage (found in the ear and epiglottis).
     */
    public function isCartilage(): bool
    {
        return in_array($this, [
            self::EPIGLOTTIS,
            self::THYROID_CARTILAGE,
            self::CRICOID_CARTILAGE,
            self::ARYTENOID_CARTILAGE,
            self::CORNICULATE_CARTILAGE,
            self::CUNEIFORM_CARTILAGE,
            self::MEDIAL_MENISCUS,
            self::LATERAL_MENISCUS,
        ]);
    }

    /**
     * Joints are defined as the connections between bones that allow for movement and provide structural support. 
     * They can be classified based on their structure (fibrous, cartilaginous, synovial) and function (synarthrosis, amphiarthrosis, diarthrosis).
     */
    public function isJoint(): bool
    {
        return in_array($this, [
            self::TEMPOROMANDIBULAR_JOINT,
            self::SHOULDER_JOINT,
            self::ELBOW_JOINT,
            self::WRIST_JOINT,
            self::SACROILIAC_JOINT,
            self::PUBIC_SYMPHYSIS,
            self::HIP_JOINT,
            self::KNEE_JOINT,
            self::ANKLE_JOINT,
        ]);
    }

    /**
     * Ligaments are defined as fibrous connective tissues that connect bones to other bones, providing stability and support to joints. 
     * They can be classified based on their location and function, such as cruciate ligaments (which cross each other in the knee) and collateral ligaments (which are located on the sides of joints).
     */
    public function isLigament(): bool
    {
        return in_array($this, [
            self::ANTERIOR_CRUCIATE_LIGAMENT,
            self::POSTERIOR_CRUCIATE_LIGAMENT,
        ]);
    }

    /**
     * Vessels are defined as tubular structures that carry blood throughout the body. 
     * This includes arteries (which carry blood away from the heart), veins (which carry blood back to the heart), and capillaries (which facilitate the exchange of oxygen, nutrients, and waste products between blood and tissues).
     */
    public function isVessel(): bool
    {
        return in_array($this, [
            self::AORTA,
            self::ABDOMINAL_AORTA,
            self::PULMONARY_ARTERY,
            self::PULMONARY_VEIN,
            self::SUPERIOR_VENA_CAVA,
            self::INFERIOR_VENA_CAVA,
            self::CORONARY_ARTERY,
            self::COMMON_CAROTID_ARTERY,
            self::INTERNAL_JUGULAR_VEIN,
            self::PORTAL_VEIN,
        ]);
    }

    /**
     * Nerves are defined as bundles of axons that transmit electrical signals between the brain, spinal cord, and other parts of the body. 
     * They can be classified into cranial nerves (which emerge directly from the brain) and spinal nerves (which emerge from the spinal cord).
     */
    public function isNerve(): bool
    {
        return in_array($this, [
            self::OPTIC_NERVE,
        ]);
    }

    /**
     * Glands are defined as organs that secrete substances for use in the body or for discharge into the surroundings. 
     * This includes endocrine glands (which secrete hormones directly into the bloodstream) and exocrine glands (which secrete substances through ducts to an epithelial surface).
     */
    public function isGland(): bool
    {
        return in_array($this, [
            self::PITUITARY_GLAND,
            self::PINEAL_GLAND,
            self::PAROTID_GLAND,
            self::SUBMANDIBULAR_GLAND,
            self::SUBLINGUAL_GLAND,
            self::THYROID_GLAND,
            self::PARATHYROID_GLAND,
            self::THYMUS,
            self::RIGHT_ADRENAL_GLAND,
            self::LEFT_ADRENAL_GLAND,
            self::PROSTATE,
            self::SEMINAL_VESICLE,
            self::OVARY,
        ]);
    }

    /**
     * Membranes are defined as thin layers of tissue that cover surfaces, line cavities, or separate different structures in the body. 
     * They can be classified based on their location and function, such as mucous membranes (which line body cavities that open to the outside), serous membranes (which line closed body cavities), and cutaneous membranes (which form the skin).
     */
    public function isMembrane(): bool
    {
        return in_array($this, [
            self::DURA_MATER,
            self::ARACHNOID_MATER,
            self::PIA_MATER,
            self::TYMPANIC_MEMBRANE,
            self::CONJUNCTIVA,
            self::PLEURA,
            self::PERICARDIUM,
            self::PERITONEUM,
            self::BUCCAL_MUCOSA,
        ]);
    }

    /**
     * Organs are defined as specialized structures composed of different tissues that perform specific functions in the body. 
     * They can be classified based on their location and function, such as the heart (which pumps blood), lungs (which facilitate gas exchange), liver (which processes nutrients), and kidneys (which filter waste from the blood).
     */
    public function isOrgan(): bool
    {
        return in_array($this, [
            self::HEART,
            self::RIGHT_LUNG,
            self::LEFT_LUNG,
            self::LIVER,
            self::GALLBLADDER,
            self::PANCREAS,
            self::SPLEEN,
            self::STOMACH,
            self::DUODENUM,
            self::JEJUNUM,
            self::ILEUM,
            self::CECUM,
            self::APPENDIX,
            self::ASCENDING_COLON,
            self::TRANSVERSE_COLON,
            self::DESCENDING_COLON,
            self::SIGMOID_COLON,
            self::RECTUM,
            self::RIGHT_KIDNEY,
            self::LEFT_KIDNEY,
            self::URINARY_BLADDER,
            self::UTERUS,
            self::TESTIS,
        ]);
    }

    /**
     * Soft tissues are defined as the tissues that connect, support, or surround other structures and organs of the body, excluding bones and cartilage. 
     * This includes muscles, tendons, ligaments, blood vessels, nerves, and connective tissues.
     */
    public function isSoftTissue(): bool
    {
        return !$this->isBone() && !$this->isCartilage();
    }
}
