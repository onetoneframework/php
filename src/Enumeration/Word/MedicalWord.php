<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Enumeration;

abstract class MedicalWord
{
    public const VENTRICULAR_TACHYARRHYTHMIA = "ventricular_tachyarrhythmia";
    public const INTERCALATED_DISCS = "intercalated_discs";
    public const LUMBRICALS = "lumbricals";
    public const LYMPHATIC = "lymphatic";
    public const PHARYNX = "pharynx";
    public const NASOPHARYNX = "nasopharynx";
    public const SEVEN_CERVICAL_VERTEBRAE = "seven_cervical_vertebrae";
    public const OROPHARYNX = "oropharynx";
    public const SUBCLAVIAN_ARTERY = "subclavian_artery";
    public const RIGHT_SUBCLAVIAN_ARTERY = "right_subclavian_artery";
    public const LEFT_SUBCLAVIAN_ARTERY = "left_subclavian_artery";
    public const SYMPATHETIC_TRUNK = "sympathetic_trunk";
    public const INTERNAL_CAROTID_ARTERY = "internal_carotid_artery";
    public const ABDUCENS_NERVE = "abducens_nerve";
    public const TROCHLEAR_NERVE = "trochlear_nerve";
    public const OPHTHALMIC_NERVE = "ophthalmic_nerve";
    public const OCULOMOTOR_NERVE = "oculomotor_nerve";
    public const THALAMUS = "thalamus";
    public const MEDULLA_OBLONGATA = "medulla_oblongata";
    public const NUCLEUS_CUNEATUS = "nucleus_cuneatus";
    public const NUCLEUS_GRACILIS = "nucleus_gracilis";
    public const LOWER_LIMB = "lower_limb";
    public const UPPER_LIMB = "upper_limb";
    public const FACIAL_VEIN = "facial_vein";
    public const PAROTID = "parotid";
    public const MASTOID = "mastoid";
    public const OSTEOPOROSIS = "osteoporosis";
    /**
     * Neck pain
     * 
     * Cause
     * 
     * 1.Poor posture or lengthy periods with the neck at an awkward angle
     * 
     * 2.Injuries that involve a sudden neck movement
     * 
     * 3.Long-term stress that causes clenching of neck and shoulder muscles
     * 
     * 4.Bone conditions, such as arthritis or osteoporosis
     * 
     * 5.Diseases or conditions that affect the spine
     * @var string
     */
    public const CERVICALGIA = "cervicalgia";
    public const AXIAL_NECK_PAIN = "axial_neck_pain";
    public const SUPINATOR = "supinator";
    public const GROWTHS = "growths";
    public const DEGENERATIVE_DISK_DISEASE = "degenerative_disk_disease";
    public const STEROID_INJECTIONS = "steroid_injections";
    public const ELECTRODIAGNOSTIC_TESTS = "electrodiagnostic_tests";
    public const TRANSCUTANEOUS_ELECTRICAL_NERVE_STIMULATION = "transcutaneous_electrical_nerve_stimulation";
    public const MAGNETIC_RESONANCE_IMAGING = "magnetic_resonance_imaging";
    public const COMPUTED_TOMOGRAPHY_SCAN = "computed_tomography_scan";
    public const ABDUCTOR_POLLICIS_LONGUS = "abductor_pollicis_longus";
    public const EXTENSOR_INDICIS = "extensor_indicis";
    public const EXTENSOR_POLLICIS_LONGUS_AND_BERVIS = "extensor_pollicis_longus_and_bervis";
    public const LINGULAR_TONSIL = "lingular_tonsil";
    public const PHARYNGEAL_TONSIL = "pharyngeal_tonsil";
    public const TUBAL_TONSIL = "tubal_tonsil";
    public const SUPRAMANDIBULAR = "supramandibular";
    public const INFERIOR_DEEP_CERVICAL = "inferior_deep_cervical";
    public const INTERNAL_JUGULAR_VEIN = "internal_jugular_vein";
    public const RETROMANDIBULAR = "retromandibular";
    public const EXTERNAL_CAROTID_ARTERY = "external_carotid_artery";
    public const ACROMION_PROCESS_OF_SCAPULA = "acromion_process_of_scapula";
    public const LEVATOR_SCAPULAE = "levator_scapulae";
    public const POSTERIOR_ACRICULAR_VEIN = "posterior_acricular_vein";
    public const POSTERIOR_EXTERNAL_JUGULAR_VEIN = "posterior_external_jugular_vein";
    public const OCCIPITAL = "occipital";
    public const MAXILLARY_ARTERY = "maxillary_artery";
    public const SUPERFICIAL_TEMPORAL_ARTERY = "superficial_temporal_artery";
    public const CERVICAL_PLEXUS = "cervical_plexus";
    public const PHRENIC_NERVE = "phrenic_nerve";
    public const LEFT_PHRENIC_NERVE = "left_phrenic_nerve";
    public const VASTUS_MEDIALIS = "vastus_medialis";
    public const VASTUS_LATERALIS = "vastus_lateralis";
    public const SARTORIUS = "sartorius";
    public const PECTINEUS = "pectineus";
    public const PERICARDIUM = "pericardium";
    public const RIGHT_DOME_OF_THE_DIAPHRAGM = "right_dome_of_the_diaphragm";
    public const HYPOGLOSSAL_NERVE = "hypoglossal_nerve";
    public const TRANSVERSE_CENVICAL = "transverse_cenvical";
    public const ANSA_CERVICALLS = "ansa_cervicalls";
    public const NERVES_TO_GENIOHYOID_AND_THYROHYOID = "nerves_to_geniohyoid_and_thyrohyoid";
    public const LARYNGOPHARYNX = "laryngopharynx";
    public const PUBIC_SYMPHYSIS = "pubic_symphysis";
    public const OBTURATOR_MEMBRANE = "obturator_membrane";
    public const OBTURATOR_CANAL = "obturator_canal";
    public const SUPERIOR_PARATHRYROID_GLAND = "superior_parathryroid_gland";
    public const INFERIOR_PARATHRYROID_GLAND = "inferior_parathryroid_gland";
    public const INFERIOR_THYROID_ARTERY = "inferior_thyroid_artery";
    public const SOFT_PALATE = "soft_palate";
    public const REDUCE_PAIN = "reduce_pain";
    public const SOFT_TISSUE_DAMAGE = "soft_tissue_damage";
    public const ATHEROSCLEROSIS = "atherosclerosis";
    public const CEREBROSPINAL_FLUID = "cerebrospinal_fluid";
    public const EPIGLOTTIS = "epiglottis";
    public const ORAL_CAVITY = "oral_cavity";
    public const THYROID_CARTILAGE = "thyroid_cartilage";
    public const DEEP_CERVICAL_LYMPH_NODES = "deep_cervical_lymph_nodes";
    public const ESOPHAGUS = "esophagus";
    public const THYROID_GLAND = "thyroid_gland";
    public const OBLIQUE_ARYTENOID_MUSCLE = "oblique_arytenoid_muscle";
    public const POSTERIOR_CRICOARYTENOID_MUSCLE = "posterior_cricoarytenoid_muscle";
    public const TRANSVERSE_ARYTENOID_MUSCLE = "transverse_arytenoid_muscle";
    public const LYMPHATIC_DRAINAGE = "lymphatic_drainage";
    public const VENOUS = "venous";
    public const VENOUS_DRAINAGE = "venous_drainage";
    public const ARATHYROID_GLANDS = "arathyroid_glands";
    public const QUADRATUS_PLANTAE = "quadratus_plantae";
    public const LEFT_ANTERIOR_DESCENDING_ARTERY = "left_anterior_descending_artery";
    public const RIGHT_CORONARY_ARTERY = "right_coronary_artery";
    public const RIGHT_COMMON_CAROTID_ARTERY = "right_common_carotid_artery";
    public const SINOATRIAL_NODE = "sinoatrial_node";
    public const LEFT_MAIN_CORONARY_ARTERY = "left_main_coronary_artery";
    public const CIRCUMFLEX_BRANCH_OF_THE_LEFT_CORONARY_ARTERY = "circumflex_branch_of_the_left_coronary_artery";
    public const BUNDLE_OF_HIS = "bundle_of_his";
    public const LEFT_CIRCUMFLEX_ARTERY = "left_circumflex_artery";
    public const BIOLOGICAL_MEMBRANE = "biological_membrane";
    public const PHOSPHOLIPID = "phospholipid";
    public const PHYSICAL_THERAPY = "physical_therapy";
    public const BREASTBONE = "breastbone";
    public const HYDROPHILE = "hydrophile";
    public const POSTERIOR_COMMISSURE = "posterior_commissure";
    public const PHOSPHOLIPID_BILAYER = "phospholipid_bilayer";
    public const LIPID_BILAYER = "lipid bilayer";
    public const RENAL_CELL_CARCINOMA = "renal_cell_carcinoma";
    public const RADICULOPATHY = "radiculopathy";
    public const CHRONIC_PYELONEPHRITIS = "chronic_pyelonephritis";
    public const OCCIPITAL_NEURALGIA = "occipital_neuralgia";
    public const CREUTZFELDT_JAKOB_DISEASE = "creutzfeldt_jakob_disease";
    public const TEMPORAL_ARTERITIS = "temporal_arteritis";
    public const ANKYLOSING_SPONDYLITIS = "ankylosing_spondylitis";
    public const TUBERCULOUS_OSTEOMYELITIS = "tuberculous_osteomyelitis";
    public const VENTRICULAR_GALLOP_RHYTHM = "ventricular_gallop_rhythm";
    public const CARDIOMEGALY = "cardiomegaly";
    public const TACHYPNEA = "tachypnea";
    public const VERTEBRA_PROMINENS = "vertebra_prominens";
    public const WHEEZE = "wheeze";
    public const CYANOSIS = "cyanosis";
    public const DYSPNEA = "dyspnea";
    public const VENOUS_CONGESTION = "venous_congestion";
    public const HEPATOMEGALY = "hepatomegaly";
    public const TENDON = "tendon";
    /**
     * Neck injury
     * @var string
     */
    public const WHIPLASH = "whiplash";
    /**
     * Feeling faint, woozy, weak or unsteady
     * @var string
     */
    public const DIZZINESS = "dizziness";
    /**
     * feeling of discomfort or sickness in the stomach that may come with an urge to vomit
     * @var string
     */
    public const NAUSEA = "nausea";
    public const PERIPHERAL_EDEMA = "peripheral_edema";
    public const NUCHAL_LIGAMENT = "nuchal_ligament";
    public const LIGAMENTUM_FLAVUM = "ligamentum_flavum";
    public const INTERSPINAL_LIGAMENT = "interspinal_ligament";
    public const BIFID_SPINOUS_PROCESS = "bifid_spinous_process";
    public const GROOVE_FOR_VERTEBRAL_ARTERY = "groove_for_vertebral_artery";
    public const ENTRICULAR_SEPTAL_DEFECT = "entricular_septal_defect";
    public const PATENT_DUCTUS_ARTERIOSUS = "patent_ductus_arteriosus";
    public const COARCTATION_OF_THE_AORTA = "coarctation_of_the_Aorta";
    public const ASCENDING_AORTA = "ascending_aorta";
    public const PULMONARY_VASCULAR_RESISTANCE = "pulmonary_vascular_resistance";
    public const MYOCARDITIS = "myocarditis";
    public const HYPOCALCEMIA = "hypocalcemia";
    public const TACHARRHYTHMIA = "tacharrhythmia";
    public const IRREGULAR_MONOMORPHIC = "irregular_monomorphic";
    public const GLYCOGEN_STORAGE_DISEASE = "glycogen_storage_disease";
    public const VERTEBRO_BASILAR_ARTERY_SYNDROME = "vertebro_basilar_artery_syndrome";
    public const TACHYCARDIA = "tachycardia";
    public const REENTRY_PATHWAY = "reentry_pathway";
    public const VENTRICULAR_TACHYCARDIA = "ventricular_tachycardia";
    public const SUPRAVENTRICULAR_TACHYCARDIA = "supraventricular_tachycardia";
    public const CONGENITAL_COMPLETE_ATRIOVENTRICULAR_BLOCK = "congenital_complete_atrioventricular_block";
    public const BRAIN_ARTERIOVENOUS_MALFORMATION = "brain_arteriovenous_malformation";
    public const ENDOCARDIAL_FIBROELASTOSIS = "endocardial_fibroelastosis";
    public const FETAL_ENDOMYOCARDIAL_FIBROSIS = "fetal_Endomyocardial_Fibrosis";
    public const ENDOCARDIAL_DYSPLASIA = "endocardial_Dysplasia";
    public const LEFT_CORONARY_ARTERY = "left_coronary_artery";
    public const KAWASAKI_SYNDROME = "kawasaki_syndrome";
    public const CORONARY_ARTERY = "coronary_artery";
    public const EBSTEIN_ANOMALY = "ebstein_anomaly";
    public const NONCOMPACTION_OF_VENTRICULAR_MYOCARDIUM = "noncompaction_of_Ventricular_Myocardium";
    public const RESTRICTIVE_CARDIOMYOPATHY = "restrictive_cardiomyopathy";
    public const CARDIOMYOPATHY = "cardiomyopathy";
    public const PATHOPHYSIOLOGY = "pathophysiology";
    public const CERVICAL_SPINE = "cervical_spine";
    public const HYOID_BONE = "hyoid_bone";
    public const SUBOCCIPITAL_MUSCLE = "suboccipital_muscle";
    public const SUPRAHYOID_MUSCLE = "suprahyoid_muscle";
    public const INFRAHYOID_MUSCLE = "infrahyoid_muscle";
    public const SCALENE_MUSCLE = "scalene_muscle";
    public const MIDDLE_SCALENE = "middle_scalene";
    public const POSTERIOR_SCALENE = "posterior_scalene";
    public const ANTERIOR_SCALENE = "anterior_scalene";
    public const THYROHYOID = "thyrohyoid";
    public const ANTERIOR_SPINOTHALAMIC_TRACT = "anterior_spinothalamic_tract";
    public const LATERAL_SPINOTHALAMIC_TRACT = "lateral_spinothalamic_tract";
    public const OMOHYOID = "omohyoid";
    public const STERNOTHYROID = "sternothyroid";
    public const STERNOHYOID = "sternohyoid";
    public const COCCYX = "coccyx";
    public const GENIOHYOID = "geniohyoid";
    public const HYLOHYOID = "hylohyoid";
    public const STYLOHYOID = "stylohyoid";
    public const DIGASTRIC = "digastric";
    public const VERTEBRAL_ARTERY = "vertebral_artery";
    public const OBLIQUUS_CAPITIS_SUPERIOR = "obliquus_capitis_superior";
    public const OBLIQUUS_CAPITIS_INFERIOR = "obliquus_capitis_inferior";
    public const RECTUS_CAPITIS_POSTERIOR_MINOR = "rectus_capitis_posterior_minor";
    public const RECTUS_CAPITIS_POSTERIOR_MAJOR = "rectus_capitis_posterior_major";
    public const CERVICAL_VERTEBRAE = "cervical_vertebrae";
    public const INTERVERTEBRAL_DISCS = "intervertebral_discs";
    public const THORACIC_VERTEBRAE = "thoracic_vertebrae";
    public const THORACIC_CAVITY = "thoracic_cavity";
    public const LUMBAR_VERTEBRAE = "lumbar_vertebrae";
    public const PULMONARY_ARTERY_PRESSURES = "pulmonary_artery_pressures";
    public const TRICUSPID_REGURGITATION = "tricuspid_regurgitation";
    public const ABDOMINAL_DISTENSION = "abdominal_distension";
    public const ERYTHROCYTE_SEDIMENTATION_RATE = "erythrocyte_sedimentation_rate";
    public const MYOCARDIAL_INFARCTION = "myocardial_infarction";
    public const ABDOMINAL_PAIN = "abdominal_pain";
    public const VOMITING = "vomiting";
    public const METHAMPHETAMINES = "methamphetamines";
    public const GALLBLADDER = "gallbladder";
    public const BEAU_LINE = "beau_line";
    public const RHINORRHEA = "rhinorrhea";
    public const PULMONARY_INFILTRATE = "pulmonary_infiltrate";
    public const DESQUAMATION = "desquamation";
    public const PARALYTIC_ILEUS = "paralytic_ileus";
    public const JAUNDICE = "jaundice";
    public const SERUM_TRANSAMINASE = "serum_transaminase";
    public const ALANINE_AMINOTRANSFERASE = "alanine_aminotransferase";
    public const C_REACTIVE_PROTEIN = "c_reactive_protein";
    public const HYPOALBUMINEMIA = "hypoalbuminemia";
    public const ACUTE_PHASE_PROTEINS = "acute_Phase_Proteins";
    public const THROMBOCYTOSIS = "thrombocytosis";
    /**
     * It is same as Accessory nerve
     */
    public const CRANIAL_NERVE_XI = "cranial_nerve_xi";
    public const CHRONIC_MYELOID_LEUKEMIA = "chronic_myeloid_leukemia";
    /**
     * The **accessory nerve** (also known as cranial nerve XI) controls specific muscles in the **neck** and **shoulders**. These muscles are mainly:
     * Sternocleidomastoid muscle, Trapezius muscle
     * 
     * So, the accessory nerve is **directly involved in the following:**
     *   **Head movement:** rotating, tilting, flexing
     *   **Shoulder movement:** shrugging, lifting, rotating the scapula
     *   **Neck posture**
     * 
     * Therefore, if the accessory nerve is damaged, it can lead to:
     *   Weakness or paralysis of the SCM and trapezius muscles.
     *   Difficulty turning the head, shrugging the shoulders, or raising the arm above the head.
     *   Drooping of the shoulder.
     *   Pain in the neck and shoulder.
     * @var string
     */
    public const ACCESSORY_NERVE = "accessory_nerve";
    public const ACCELERATED_PHASE = "accelerated_phase";
    public const ARTHRITIS = "arthritis";
    public const MUSCULOSKELETAL_INJURY = "musculoskeletal_injury";
    public const CHRONIC_STABLE_PHASE = "chronic_stable_phase";
    public const HEMATOPOIETIC_STEM_CELL_TRANSPLANTATION = "hematopoietic_stem_cell_transplantation";
    public const ALLOGENEIC_HEMATOPOIETIC_CELL_TRANSPLANTATION = "allogeneic_hematopoietic_cell_transplantation";
    public const STERILE_PYURIA = "sterile_pyuria";
    public const PROTEINURIA = "proteinuria";
    public const PERINEAL_RASH = "perineal_rash";
    public const TRANSVERSE_FURROWS = "transverse_furrows";
    public const FACIAL_PALSY = "facial_palsy";
    public const ANTERIOR_ARCH = "anterior_arch";
    public const ODONTOID_PROCESS = "odontoid_process";
    public const SPINOUS_PROCESS = "spinous_process";
    public const LAMINA = "lamina";
    public const PEDICEL = "pedicel";
    public const TRAPEZIUS = "trapezius";
    /**
     * This large muscle spans your upper back and shoulders. It helps you shrug your shoulders, tilt your head back, and move your shoulder blades.
     * @var string
     */
    public const TRAPEZIUS_MUSCLE = "trapezius_muscle";
    public const DORSAL_ARCH = "dorsal_arch";
    public const STERNOCLEIDOMASTOID = "sternocleidomastoid";
    /**
     * This muscle runs along the side of your neck. It allows you to rotate your head, tilt your head to the side, and flex your neck forward.
     * @var string
     */
    public const STERNOCLEIDOMASTOID_MUSCLE = "sternocleidomastoid_muscle";
    public const ATLANTO_OCCIPITAL_JOINT = "atlanto_occipital_joint";
    public const ATLANTO_AXIAL_JOINT = "atlanto_axial_joint";
    public const UNCOVERTEBRAL_JOINTS = "uncovertebral_joints";
    public const INFERIOR_ARTICULAR_FACET = "inferior_articular_facet";
    public const SUPERIOR_ARTICULAR_FACET = "superior_articular_facet";
    public const TRANSVERSE_FORAMEN = "transverse_foramen";
    public const FACET_JOINT = "facet_joint";
    public const POSTERIOR_TUBERCLE = "posterior_tubercle";
    public const POSTERIOR_ARCH = "posterior_arch";
    public const ANTERIOR_TUBERCLE = "anterior_tubercle";
    public const RIGHT_VENTRICULAR_OUTFLOW_TRACT = "right_ventricular_outflow_tract";
    public const LEFT_VENTRICULAR_OUTFLOW_TRACT = "left_ventricular_outflow_tract";
    public const PRESSURE_GRADIENT = "pressure_gradient";
    public const POSITRON_EMISSION_TOMOGRAPHY = "positron_emission_tomography";
    public const DISTAL_LEFT_ANTERIOR_DESCENDING_CORONARY_ARTERY = "distal_left_anterior_descending_coronary_artery";
    public const DIASTOLIC_DYSFUNCTION = "diastolic_dysfunction";
    public const ASYMMETRIC_SEPTAL_HYPERTROPHY = "asymmetric_septal_hypertrophy";
    public const HYPERTROPHIC_CARDIOMYOPATHY = "hypertrophic_cardiomyopathy";
    public const HEART_RATE_RESERVE = "heart_Rate_Reserve";
    public const METABOLIC_EQUIVALENT_TASK = "metabolic_Equivalent_Task";
    public const RATING_OF_PERCEIVED_EXERTION = "rating_of_perceived_exertion";
    public const SKELETAL_MUSCLE = "skeletal_muscle";
    public const SMOOTHMUSCLE = "smoothmuscle";
    public const CARDIAC_MUSCLE = "cardiac_muscle";
    public const SLIDING_FILAMENTMECHANISM = "sliding_filamentmechanism";
    public const CROSS_BRIDGE_CYCLE = "cross_bridge_cycle";
    public const PLANTAR_FASCIITIS = "plantar_fasciitis";
    public const PNEUMONIA = "pneumonia";
    public const CHONDROMALACIA_PATELLA = "chondromalacia_patella";    
    public const CARPAL_TUNNEL_SYNDROME = "carpal_tunnel_syndrome";
    public const ADENOSINE_TRIPHOSPHATE = "adenosine_triphosphate";
    public const CENTRAL_NERVOUS_SYSTEM = "central_Nervous_System";
    public const SPLINE_INTERPOLATION = "spline_interpolation";
    public const CARDIOGENIC_SHOCK = "cardiogenic_shock";
    public const LEFT_TO_RIGHT_SHUNT = "left_to_right_shunt";
    public const INTERCOSTAL_ARTERIES = "intercostal_arteries";
    public const CORONARY_ARTERIAL = "coronary_arterial";
    public const SOCIODEMOGRAPHIC = "sociodemographic";
    public const ENDOMYOCARDIAL_BIOPSY = "endomyocardial_biopsy";
    public const PRIMARY_MYOCARDIAL_DISEASE = "primary_myocardial_disease";
    public const INTRACARDIAC_NEEDLE_BIOPSY = "intracardiac_needle_biopsy";
    public const ANTECUBITAL_FOSSA = "antecubital_fossa";
    public const INGUINAL_REGION = "inguinal_region";
    public const CARDIAC_CONDUCTION_SYSTEM = "cardiac_conduction_system";
    public const WILLSON_DISEASE = "willson_disease";
    public const SUBARACHNOID_HEMORRHAGE = "subarachnoid_hemorrhage";
    public const TRANSIENT_CEREBRAL_ISCHEMIA = "transient_cerebral_ischemia";
    public const OTHER_PARKINSONISM = "other_parkinsonism";
    public const GLOSSOPHARYNGEAL_NEURALGIA = "glossopharyngeal_neuralgia";
    public const PRIMARY_CNS_LYMPHOMA = "primary_cns_lymphoma";
    public const TRAUMATIC_SUBDURAL_HEMORRHAGE = "traumatic_subdural_hemorrhage";
    public const HYPERTELORISM = "hypertelorism";
    public const PROSOPAGNOSIA = "prosopagnosia";
    public const STURGE_WEBER_SYNDROME = "sturge_Weber_Syndrome";
    public const HYDROCEPHALUS = "hydrocephalus";
    public const MENINGIOMA = "meningioma";
    public const WERNICKE_KORSAKOFF_SYNDROME = "wernicke_korsakoff_syndrome";
    public const CRANIOSYNOSTOSIS = "craniosynostosis";
    public const GLIOBLASTOMA = "glioblastoma";
    public const HEMORRHAGE = "hemorrhage";
    public const CEREBRAL_HEMORRHAGE = "cerebral_hemorrhage";
    public const CEREBRAL_PALSY = "cerebral_palsy";
    public const CEREBRAL_INFARCTION = "cerebral_infarction";
    public const OCCLUSION_OF_CEREBRAL_ARTERY = "occlusion_of_cerebral_artery";
    public const BRAIN_ABSCESS = "brain_abscess";
    public const SPINAL_CORD_TUMOR = "spinal_Cord_tumor";
    public const MENINGITIS = "meningitis";
    public const TUBERCULOUS_MENINGITIS = "tuberculous_meningitis";
    public const VERTEBRAL_FRACTURES = "vertebral_fractures";
    public const CEREBRAL_ARTERIOVENOUS_MALFORMATION = "cerebral_arteriovenous_malformation";
    public const GERSTMANN_SYNDROME = "gerstmann_Syndrome";
    public const ARRHYTHMIA = "arrhythmia";
    public const LUMBAR_SPONDYLOLISTHESIS = "lumbar_spondylolisthesis";
    public const URINARY_TRACT_INFECTION = "urinary_tract_infection";
    public const COMPLETE_CORD_INJURY = "complete_cord_injury";
    public const FORAMEN_OVALE = "foramen_ovale";
    public const FORAMEN_MAGNUM = "foramen_magnum";
    public const COMMON_CAROTID_ARTERY = "common_carotid_artery";
    public const SEPTAL_LEAFLET = "septal_leaflet";
    public const ANTERIOR_LEAFLET = "anterior_leaflet";
    public const POSTERIOR_LEAFLET = "posterior_leaflet";
    public const TRICUSPID_VALVE = "tricuspid_valve";
    public const LEAFLET = "leaflet";
    public const ANNULUS = "annulus";
    public const PAPILLARY_MUSCLE = "papillary_muscle";
    public const BRACHIOCEPHALIC_ARTERY = "brachiocephalic_artery";
    public const INNOMINATE_ARTERY = "innominate_artery";
    public const PROGRESSIVE_SUPRANUCLEAR_PALSY = "progressive_supranuclear_palsy";
    public const CHORDAE = "chordae";
    public const CHORDAE_TENDINEAE = "chordae_Tendineae";
    public const MYOCARDIAL_LAYER = "myocardial_layer";
    public const MYOCARDIAL_TISSUE = "myocardial_tissue";
    public const FIBROUS_TRANSFORMATION = "fibrous_transformation";
    public const RIGHT_VENTRICULAR_EJECTION_FRACTION = "right_ventricular_ejection_fraction";
    public const NONSPECIFIC_INTRAVENTRICULAR_CONDUCTION_DELAY = "nonspecific_intraventricular_conduction_delay";
    public const ROENTGENOGRAM = "roentgenogram";
    public const NORMAL_SINUS_RHYTHM = "normal_sinus_rhythm";
    public const PULMONARY_ATRESIA = "pulmonary_atresia";
    public const HOLOSYSTOLIC_REGURGITATION_MURMUR = "holosystolic_regurgitation_murmur";
    public const GALLOP_RHYTHM = "gallop_rhythm";
    public const WPW_SYNDROME = "wPW_syndrome";
    public const TRABECULAR_PORTION = "trabecular_portion";
    public const INFUNDIBULAR_PORTION = "infundibular_portion";
    public const PREOPERATIVE = "preoperative";
    public const CORONARY_SINUS = "coronary_sinus";
    public const PULMONARY_SEQUESTRATION = "pulmonary_sequestration";
    public const PULMONARY_CAPILLARY_WEDGE_PRESSURE = "pulmonary_capillary_wedge_pressure";
    public const COSTOCERVICAL_TRUNK = "costocervical_trunk";
    public const THYROCERVICAL_TRUNK = "thyrocervical_trunk";
    public const PULMONARY_ARTERIOVENOUS_MALFORMATION = "pulmonary_Arteriovenous_Malformation";
    public const PULMONARY_ENDOMETRIOSIS = "pulmonary_endometriosis";
    public const PULMONARY_ARTERIAL_HYPERTENSION = "pulmonary_arterial_hypertension";
    public const PULMONARY_ALVEOLAR_PROTEINOSIS = "pulmonary_alveolar_proteinosis";
    public const IDIOPATHIC_PULMONARY_FIBROSIS = "idiopathic_pulmonary_fibrosis";
    public const PULMONARY_NODULE = "pulmonary_nodule";
    public const CHRONIC_OBSTRUCTIVE_PULMONARY_DISEASE = "chronic_Obstructive_Pulmonary_Disease";
    public const SINUSOIDAL_COMMUNICATION = "sinusoidal_communication";
    public const MITRAL_VALVULOTOMY = "mitral_valvulotomy";
    public const PULMONARY_TRUNK = "pulmonary_trunk";
    public const MAIN_PULMONARY_ARTERY = "main_pulmonary_artery";
    public const SUBSEGMENTAL_PULMONARY_ARTERIES = "subsegmental_pulmonary_arteries";
    public const INTRALOBULAR_ARTERIES = "intralobular_arteries";
    public const ENDOCARDIAL_TUBES = "endocardial_tubes";
    public const SUBSTANTIA_GELATINOSA = "substantia_gelatinosa";
    public const POSTERIOR_SPINOCEREBELLAR_TRACT = "posterior_spinocerebellar_tract";
    public const CUNEOCEREBELLAR_TRACT = "cuneocerebellar_tract";
    public const ROSTRAL_SPINOCEREBELLAR_TRACT = "rostral_spinocerebellar_tract";
    public const BULBUS_CORDIS = "bulbus_cordis";
    public const SADDLE_EMBOLUS = "saddle_embolus";
    public const OCCIPITAL_BONE = "occipital_bone";
    public const TRUNCUS_ARTERIOSU = "truncus_arteriosu";
    public const IRREGULAR_HEARTBEAT = "irregular_heartbeat";
    public const IRREGULAR_BONES = "irregular_bones";
    public const VENTRICULAR_FIBRILLATION = "ventricular_fibrillation";
    public const ULNAR_ARTERY = "ulnar_artery";
    public const RADIAL_ARTERY = "radial_artery";
    public const EPIGLOTTITIS = "epiglottitis";
    public const RETROPHARYNGEAL_ABSCESS = "retropharyngeal_abscess";
    public const CAROTID_ARTERY = "carotid_artery";
    public const SPONDYLOSIS = "spondylosis";
    public const MONOCHROMATIC = "monochromatic";
    public const PHOTOTHERAPY = "phototherapy";
    /**
     * When a cell is working to repair itself, it needs a great deal of energy.
     * 
     * Most cells continue to work at their usual rate, which is why the repair of some tissues takes so long. 
     * 
     * In some instances, the cells stay so busy dealing with inflammation and bi-products that are present in the injured tissue
     * 
     * that they don’t have enough energy left to provide effective repair
     * 
     * With the use of lasers, the cells are stimulated and their activity is increased so that they can perform better, faster, and more effectively
     * @var string
     */
    public const LASER_THERAPY = "laser_therapy";    
    public const MITOCHONDRIA = "mitochondria";
    public const METABOLISM = "metabolism";
    public const KREBS_CYCLE = "krebs_cycle";
    public const ACCELERATE_TISSUE_REPAIR = "accelerate_tissue_repair";
    public const PHYSIOTHERAPY = "physiotherapy";
    public const CERVICAL_SPINAL_STENOSIS = "cervical_spinal_stenosis";
    public const CAROTID_ARTERY_DISSECTION = "carotid_artery_dissection";
    public const BRACHIAL_ARTERY = "brachial_artery";
    public const FEMORAL_ARTERY = "femoral_artery";
    public const POSTERIOR_TIBIAL_ARTERY = "posterior_tibial_artery";
    public const DORSALIS_PEDIS = "dorsalis_pedis";
    public const POPLITEAL_ARTERY = "popliteal_artery";
    public const ABDOMINAL_AORTA = "abdominal_aorta";
    public const FACIAL_ARTERY = "facial_artery";
    public const BASILAR_ARTERY = "basilar_artery";
    public const BRADYCARDIA = "bradycardia";
    public const MARFAN_S_SYNDROME = "marfan_s_syndrome";
    public const GAP_JUNCTION = "gap_junction";
    public const EMBRYO = "embryo";
    public const ENDOPLASMIC_RETICULUM = "endoplasmic_reticulum";
    public const SARCOPLASMIC_RETINACULUM = "sarcoplasmic_retinaculum";
    public const MITRAL_REGURGITATION = "mitral_regurgitation";
    public const OBTUSE_MARGINAL_BRANCH = "obtuse_marginal_branch";
    public const POSTERIOR_GLOTTIS = "posterior_Glottis";
    public const T_TUBULES = "t_tubules";
    public const POSTERIOR = "posterior";
    public const STIFFNESS = "stiffness";
    public const OSTEOARTHRITIS = "osteoarthritis";
    public const DEGENERATIVE_ARTHRITIS = "degenerative_arthritis";
    public const DEGENERATIVE_JOINT_DISEASE = "degenerative_joint_disease";
    public const TRANSVERSE_TUBULES = "transverse_tubules";
    public const SUPERIOR_VENA_CAVA = "superior_vena_cava";
    public const INFERIOR_VENA_CAVA = "inferior_vena_cava";
    public const MITRAL_VALVE = "mitral_valve";
    public const AORTIC_VALVE = "aortic_valve";
}