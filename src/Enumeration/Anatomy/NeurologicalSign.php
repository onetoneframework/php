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
 * This enum represents various neurological signs that can be observed during a neurological examination. 
 * These signs are categorized based on their clinical significance and the underlying neurological pathways they assess, such as upper motor neuron (UMN) signs, lower motor neuron (LMN) signs, deep tendon reflexes, superficial reflexes, meningeal signs, cerebellar tests, cranial nerve tests, sensory tests, and special neurological signs.
 */
enum NeurologicalSign: string
{
    // -------------------------------------------------------------------------
    // UMN signs
    // -------------------------------------------------------------------------
    case BABINSKI_SIGN = 'babinski_sign';
    case HOFFMANN_SIGN = 'hoffmann_sign';
    case CLONUS = 'clonus';
    case HYPERREFLEXIA = 'hyperreflexia';
    case SPASTICITY = 'spasticity';
    case EXTENSOR_PLANTAR_RESPONSE = 'extensor_plantar_response';

    // -------------------------------------------------------------------------
    // LMN signs
    // -------------------------------------------------------------------------
    case AREFLEXIA = 'areflexia';
    case HYPOREFLEXIA = 'hyporeflexia';
    case FASCICULATION = 'fasciculation';
    case FLACCIDITY = 'flaccidity';
    case MUSCLE_ATROPHY = 'muscle_atrophy';

    // -------------------------------------------------------------------------
    // Deep tendon reflexes (DTR)
    // -------------------------------------------------------------------------
    case BICEPS_REFLEX = 'biceps_reflex';
    case BRACHIORADIALIS_REFLEX = 'brachioradialis_reflex';
    case TRICEPS_REFLEX = 'triceps_reflex';
    case PATELLAR_REFLEX = 'patellar_reflex';
    case ACHILLES_REFLEX = 'achilles_reflex';

    // -------------------------------------------------------------------------
    // Superficial reflexes
    // -------------------------------------------------------------------------
    case PLANTAR_REFLEX = 'plantar_reflex';
    case ABDOMINAL_REFLEX = 'abdominal_reflex';
    case CREMASTERIC_REFLEX = 'cremasteric_reflex';
    case ANAL_WINK_REFLEX = 'anal_wink_reflex';
    case CORNEAL_REFLEX = 'corneal_reflex';
    case GAG_REFLEX = 'gag_reflex';

    // -------------------------------------------------------------------------
    // Meningeal signs
    // -------------------------------------------------------------------------
    case KERNIG_SIGN = 'kernig_sign';
    case BRUDZINSKI_SIGN = 'brudzinski_sign';
    case NECK_STIFFNESS = 'neck_stiffness';
    case JOLT_ACCENTUATION = 'jolt_accentuation';

    // -------------------------------------------------------------------------
    // Cerebellar tests
    // -------------------------------------------------------------------------
    case FINGER_NOSE_TEST = 'finger_nose_test';
    case HEEL_SHIN_TEST = 'heel_shin_test';
    case DYSDIADOCHOKINESIA = 'dysdiadochokinesia';
    case ROMBERG_TEST = 'romberg_test';
    case TANDEM_GAIT = 'tandem_gait';
    case PAST_POINTING = 'past_pointing';

    // -------------------------------------------------------------------------
    // Cranial nerve tests
    // -------------------------------------------------------------------------
    case VISUAL_ACUITY_TEST = 'visual_acuity_test';
    case CONFRONTATION_VISUAL_FIELD = 'confrontation_visual_field';
    case PUPILLARY_LIGHT_REFLEX = 'pupillary_light_reflex';
    case EXTRAOCULAR_MOVEMENT_TEST = 'extraocular_movement_test';
    case FACIAL_SENSATION_TEST = 'facial_sensation_test';
    case FACIAL_STRENGTH_TEST = 'facial_strength_test';
    case RINNE_TEST = 'rinne_test';
    case WEBER_TEST = 'weber_test';
    case TONGUE_DEVIATION_TEST = 'tongue_deviation_test';

    // -------------------------------------------------------------------------
    // Sensory tests
    // -------------------------------------------------------------------------
    case PINPRICK_TEST = 'pinprick_test';
    case LIGHT_TOUCH_TEST = 'light_touch_test';
    case VIBRATION_SENSE_TEST = 'vibration_sense_test';
    case PROPRIOCEPTION_TEST = 'proprioception_test';
    case TWO_POINT_DISCRIMINATION = 'two_point_discrimination';
    case STEREOGNOSIS = 'stereognosis';
    case GRAPHESTHESIA = 'graphesthesia';
    case TEMPERATURE_SENSE_TEST = 'temperature_sense_test';

    // -------------------------------------------------------------------------
    // Special neurological signs
    // -------------------------------------------------------------------------
    case LHERMITTE_SIGN = 'lhermitte_sign';
    case TINEL_SIGN = 'tinel_sign';
    case PHALEN_TEST = 'phalen_test';
    case STRAIGHT_LEG_RAISE = 'straight_leg_raise';
    case SPURLING_TEST = 'spurling_test';
    case SLUMP_TEST = 'slump_test';
    case LASEGUE_SIGN = 'lasegue_sign';

    /** 
     * This method provides a descriptive label for each neurological sign, which can be used in clinical documentation, educational materials, or any context where a clear explanation of the sign is needed.
     */
    public function label(): string
    {
        return match ($this) {
            self::BABINSKI_SIGN => 'Babinski Sign — Extensor plantar response indicating UMN lesion',
            self::HOFFMANN_SIGN => 'Hoffmann Sign — Finger flick reflex indicating cervical UMN lesion',
            self::CLONUS => 'Clonus — Rhythmic involuntary muscle contraction indicating UMN lesion',
            self::HYPERREFLEXIA => 'Hyperreflexia — Exaggerated deep tendon reflexes',
            self::SPASTICITY => 'Spasticity — Velocity-dependent increased muscle tone (UMN)',
            self::EXTENSOR_PLANTAR_RESPONSE => 'Extensor Plantar Response — Dorsiflexion of hallux on plantar stimulation',
            self::AREFLEXIA => 'Areflexia — Complete absence of deep tendon reflexes (LMN)',
            self::HYPOREFLEXIA => 'Hyporeflexia — Diminished deep tendon reflexes (LMN)',
            self::FASCICULATION => 'Fasciculation — Spontaneous motor unit firing (LMN)',
            self::FLACCIDITY => 'Flaccidity — Loss of muscle tone (LMN)',
            self::MUSCLE_ATROPHY => 'Muscle Atrophy — Denervation-induced muscle wasting (LMN)',
            self::BICEPS_REFLEX => 'Biceps Reflex — DTR at C5–C6',
            self::BRACHIORADIALIS_REFLEX => 'Brachioradialis Reflex — DTR at C5–C6',
            self::TRICEPS_REFLEX => 'Triceps Reflex — DTR at C7',
            self::PATELLAR_REFLEX => 'Patellar Reflex (Knee Jerk) — DTR at L3–L4',
            self::ACHILLES_REFLEX => 'Achilles Reflex (Ankle Jerk) — DTR at S1–S2',
            self::PLANTAR_REFLEX => 'Plantar Reflex — Normal flexor response to sole stimulation',
            self::ABDOMINAL_REFLEX => 'Abdominal Reflex — T8–T12 superficial reflex',
            self::CREMASTERIC_REFLEX => 'Cremasteric Reflex — L1–L2 superficial reflex',
            self::ANAL_WINK_REFLEX => 'Anal Wink Reflex — S3–S5 superficial reflex',
            self::CORNEAL_REFLEX => 'Corneal Reflex — CN V afferent / CN VII efferent',
            self::GAG_REFLEX => 'Gag Reflex — CN IX afferent / CN X efferent',
            self::KERNIG_SIGN => 'Kernig Sign — Knee extension resistance with hip flexed (meningeal)',
            self::BRUDZINSKI_SIGN => 'Brudzinski Sign — Neck flexion causes involuntary hip/knee flexion',
            self::NECK_STIFFNESS => 'Neck Stiffness (Nuchal Rigidity) — Meningeal irritation sign',
            self::JOLT_ACCENTUATION => 'Jolt Accentuation — Headache worsening on horizontal head rotation',
            self::FINGER_NOSE_TEST => 'Finger-Nose Test — Cerebellar dysmetria assessment',
            self::HEEL_SHIN_TEST => 'Heel-Shin Test — Lower limb cerebellar coordination',
            self::DYSDIADOCHOKINESIA => 'Dysdiadochokinesia — Impaired rapid alternating movements (cerebellar)',
            self::ROMBERG_TEST => 'Romberg Test — Proprioception / vestibular integrity (eyes closed)',
            self::TANDEM_GAIT => 'Tandem Gait — Cerebellar ataxia assessment',
            self::PAST_POINTING => 'Past Pointing — Cerebellar dysmetria on target pointing',
            self::VISUAL_ACUITY_TEST => 'Visual Acuity Test — CN II (Snellen chart)',
            self::CONFRONTATION_VISUAL_FIELD => 'Confrontation Visual Field Test — CN II gross field assessment',
            self::PUPILLARY_LIGHT_REFLEX => 'Pupillary Light Reflex — CN II afferent / CN III efferent',
            self::EXTRAOCULAR_MOVEMENT_TEST => 'Extraocular Movement Test — CN III, IV, VI',
            self::FACIAL_SENSATION_TEST => 'Facial Sensation Test — CN V (V1/V2/V3 dermatomes)',
            self::FACIAL_STRENGTH_TEST => 'Facial Strength Test — CN VII (upper vs lower face)',
            self::RINNE_TEST => 'Rinne Test — CN VIII; air vs bone conduction comparison',
            self::WEBER_TEST => 'Weber Test — CN VIII; lateralization of bone conduction',
            self::TONGUE_DEVIATION_TEST => 'Tongue Deviation Test — CN XII; deviation toward lesion side',
            self::PINPRICK_TEST => 'Pinprick Test — Spinothalamic (pain) pathway integrity',
            self::LIGHT_TOUCH_TEST => 'Light Touch Test — Dorsal column and spinothalamic pathways',
            self::VIBRATION_SENSE_TEST => 'Vibration Sense Test — Dorsal column–medial lemniscal pathway',
            self::PROPRIOCEPTION_TEST => 'Proprioception Test (Joint Position Sense) — Dorsal column',
            self::TWO_POINT_DISCRIMINATION => 'Two-Point Discrimination — Cortical sensory processing',
            self::STEREOGNOSIS => 'Stereognosis — Object identification by touch (parietal lobe)',
            self::GRAPHESTHESIA => 'Graphesthesia — Number/letter recognition by skin trace (parietal)',
            self::TEMPERATURE_SENSE_TEST => 'Temperature Sense Test — Spinothalamic pathway',
            self::LHERMITTE_SIGN => 'Lhermitte Sign — Electric shock on neck flexion (cervical myelopathy / MS)',
            self::TINEL_SIGN => 'Tinel Sign — Paresthesia on nerve percussion (nerve regeneration)',
            self::PHALEN_TEST => 'Phalen Test — Wrist flexion reproduces symptoms (carpal tunnel)',
            self::STRAIGHT_LEG_RAISE => 'Straight Leg Raise (SLR) — L4–S1 nerve root tension (disc herniation)',
            self::SPURLING_TEST => 'Spurling Test — Neck compression reproduces radicular pain (cervical)',
            self::SLUMP_TEST => 'Slump Test — Neural tension test for lumbar radiculopathy',
            self::LASEGUE_SIGN => 'Lasègue Sign — Sciatic nerve stretch test (variant of SLR)',
        };
    }

    /** Spinal level implicated by this test, null if not spinal-level specific */
    public function spinalLevel(): ?string
    {
        return match ($this) {
            self::BICEPS_REFLEX, self::BRACHIORADIALIS_REFLEX => 'C5–C6',
            self::TRICEPS_REFLEX => 'C7',
            self::HOFFMANN_SIGN => 'C1–C4 (UMN above C5)',
            self::CREMASTERIC_REFLEX => 'L1–L2',
            self::PATELLAR_REFLEX => 'L3–L4',
            self::STRAIGHT_LEG_RAISE, self::LASEGUE_SIGN => 'L4–S1',
            self::ACHILLES_REFLEX => 'S1–S2',
            self::ANAL_WINK_REFLEX => 'S3–S5',
            self::ABDOMINAL_REFLEX => 'T8–T12',
            default => null,
        };
    }

    /** 
     * UMN signs are clinical indicators of upper motor neuron dysfunction, which can occur due to damage to the corticospinal tract, brainstem, or cerebral cortex. 
     * These signs include hyperreflexia (exaggerated reflexes), spasticity (increased muscle tone), Babinski sign (extensor plantar response), Hoffmann sign (finger flick reflex), clonus (rhythmic muscle contractions), and extensor plantar response. 
     * The presence of UMN signs can help localize neurological lesions and differentiate them from lower motor neuron (LMN) lesions, which typically present with hyporeflexia, flaccidity, and muscle atrophy.
     */
    public function isUmnSign(): bool
    {
        return in_array($this, [
            self::BABINSKI_SIGN,
            self::EXTENSOR_PLANTAR_RESPONSE,
            self::HOFFMANN_SIGN,
            self::CLONUS,
            self::HYPERREFLEXIA,
            self::SPASTICITY,
        ]);
    }

    /** 
     * LMN signs are clinical indicators of lower motor neuron dysfunction, which can occur due to damage to the anterior horn cells, peripheral nerves, neuromuscular junctions, or muscles themselves. 
     * These signs include areflexia (absence of reflexes), hyporeflexia (diminished reflexes), fasciculations (involuntary muscle twitches), flaccidity (loss of muscle tone), and muscle atrophy (wasting of muscle tissue). 
     * The presence of LMN signs can help localize neurological lesions and differentiate them from upper motor neuron (UMN) lesions, which typically present with hyperreflexia, spasticity, and other UMN signs.
     */
    public function isLmnSign(): bool
    {
        return in_array($this, [
            self::AREFLEXIA,
            self::HYPOREFLEXIA,
            self::FASCICULATION,
            self::FLACCIDITY,
            self::MUSCLE_ATROPHY,
        ]);
    }

    /**
     * Meningeal signs are clinical indicators of meningeal irritation, which can occur in conditions such as meningitis, subarachnoid hemorrhage, or other inflammatory processes affecting the meninges. 
     * These signs are elicited through specific maneuvers that stretch or irritate the meninges, and a positive sign typically reproduces the patient's symptoms (such as neck stiffness, headache, or photophobia) or elicits an involuntary response (such as resistance to neck flexion or involuntary hip/knee flexion).
     */
    public function isMeningealSign(): bool
    {
        return in_array($this, [
            self::KERNIG_SIGN,
            self::BRUDZINSKI_SIGN,
            self::NECK_STIFFNESS,
            self::JOLT_ACCENTUATION,
        ]);
    }

    /** 
     * Cerebellar tests are designed to evaluate the function of the cerebellum, which is responsible for coordinating voluntary movements, maintaining balance and posture, and facilitating motor learning. 
     * These tests assess various aspects of cerebellar function, such as coordination, balance, and the ability to perform rapid alternating movements, and they can help identify cerebellar dysfunction or lesions.
     */
    public function isCerebellarTest(): bool
    {
        return in_array($this, [
            self::FINGER_NOSE_TEST,
            self::HEEL_SHIN_TEST,
            self::DYSDIADOCHOKINESIA,
            self::ROMBERG_TEST,
            self::TANDEM_GAIT,
            self::PAST_POINTING,
        ]);
    }

    /** 
     * Deep tendon reflexes (DTRs) are elicited by tapping on a tendon with a reflex hammer, which stretches the muscle and activates the muscle spindle afferents. 
     * This leads to a monosynaptic reflex arc that results in a rapid contraction of the muscle. 
     * DTRs are important for assessing the integrity of the spinal cord segments and peripheral nerves, and they can help differentiate between upper motor neuron (UMN) and lower motor neuron (LMN) lesions based on their presence, absence, or exaggeration.
     */
    public function isDeepTendonReflex(): bool
    {
        return in_array($this, [
            self::BICEPS_REFLEX,
            self::BRACHIORADIALIS_REFLEX,
            self::TRICEPS_REFLEX,
            self::PATELLAR_REFLEX,
            self::ACHILLES_REFLEX,
        ]);
    }

    /** 
     * Superficial reflexes are elicited by stimulating the skin or mucous membranes, and they involve a reflex arc that includes sensory receptors, afferent nerves, spinal cord segments, efferent nerves, and effector muscles. 
     * These reflexes can provide information about the integrity of specific spinal cord segments and the corresponding peripheral nerves, and they are often used to assess for neurological damage or dysfunction.
     */
    public function isSuperficialReflex(): bool
    {
        return in_array($this, [
            self::PLANTAR_REFLEX,
            self::ABDOMINAL_REFLEX,
            self::CREMASTERIC_REFLEX,
            self::ANAL_WINK_REFLEX,
            self::CORNEAL_REFLEX,
            self::GAG_REFLEX,
        ]);
    }

    /**
     * Cranial nerve tests are designed to evaluate the function of the twelve cranial nerves, which control a wide range of sensory and motor functions in the head and neck. 
     * These tests can help identify lesions affecting specific cranial nerves or their nuclei, and they are essential components of the neurological examination, particularly when assessing patients with symptoms such as facial weakness, visual disturbances, hearing loss, or dysphagia.
     */
    public function isCranialNerveTest(): bool
    {
        return in_array($this, [
            self::VISUAL_ACUITY_TEST,
            self::CONFRONTATION_VISUAL_FIELD,
            self::PUPILLARY_LIGHT_REFLEX,
            self::EXTRAOCULAR_MOVEMENT_TEST,
            self::FACIAL_SENSATION_TEST,
            self::FACIAL_STRENGTH_TEST,
            self::RINNE_TEST,
            self::WEBER_TEST,
            self::CORNEAL_REFLEX,
            self::GAG_REFLEX,
            self::TONGUE_DEVIATION_TEST,
        ]);
    }

    /** 
     * Sensory tests are designed to evaluate the integrity of various sensory pathways in the nervous system, including the dorsal column–medial lemniscal pathway (for fine touch, vibration, and proprioception) and the spinothalamic pathway (for pain and temperature sensation). 
     * These tests help localize neurological lesions and assess the functional status of sensory nerves and central pathways.
     */
    public function isSensoryTest(): bool
    {
        return in_array($this, [
            self::PINPRICK_TEST,
            self::LIGHT_TOUCH_TEST,
            self::VIBRATION_SENSE_TEST,
            self::PROPRIOCEPTION_TEST,
            self::TWO_POINT_DISCRIMINATION,
            self::STEREOGNOSIS,
            self::GRAPHESTHESIA,
            self::TEMPERATURE_SENSE_TEST,
        ]);
    }

    /** 
     * Neural tension tests are designed to assess the presence of nerve root irritation or compression, often due to conditions like herniated discs, spinal stenosis, or radiculopathy. 
     * These tests involve specific maneuvers that stretch or compress the nerve roots, and a positive test typically reproduces the patient's symptoms (such as pain, tingling, or numbness) along the distribution of the affected nerve.
     */
    public function isNeuralTensionTest(): bool
    {
        return in_array($this, [
            self::STRAIGHT_LEG_RAISE,
            self::LASEGUE_SIGN,
            self::SLUMP_TEST,
            self::SPURLING_TEST,
            self::LHERMITTE_SIGN,
        ]);
    }
}
