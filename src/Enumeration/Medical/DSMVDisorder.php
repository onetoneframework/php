<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Enumeration\Medical;

enum DSMVDisorder: int
{
    // Neurodevelopmental Disorders (100–)
    case INTELLECTUAL_DISABILITY_MILD = 100;
    case INTELLECTUAL_DISABILITY_MODERATE = 101;
    case INTELLECTUAL_DISABILITY_SEVERE = 102;
    case INTELLECTUAL_DISABILITY_PROFOUND = 103;
    case GLOBAL_DEVELOPMENTAL_DELAY = 104;
    case AUTISM_SPECTRUM_DISORDER = 105;
    case ADHD_COMBINED = 106;
    case ADHD_PREDOMINANTLY_INATTENTIVE = 107;
    case ADHD_PREDOMINANTLY_HYPERACTIVE_IMPULSIVE = 108;
    case SPECIFIC_LEARNING_DISORDER_READING = 109;
    case SPECIFIC_LEARNING_DISORDER_WRITTEN_EXPRESSION = 110;
    case SPECIFIC_LEARNING_DISORDER_MATHEMATICS = 111;
    case DEVELOPMENTAL_COORDINATION_DISORDER = 112;
    case STEREOTYPIC_MOVEMENT_DISORDER = 113;
    case TOURETTE_SYNDROME = 114;
    case PERSISTENT_MOTOR_VOCAL_TIC_DISORDER = 115;
    case PROVISIONAL_TIC_DISORDER = 116;
    case LANGUAGE_DISORDER = 117;
    case SPEECH_SOUND_DISORDER = 118;
    case CHILDHOOD_ONSET_FLUENCY_DISORDER = 119;
    case SOCIAL_PRAGMATIC_COMMUNICATION_DISORDER = 120;

    // Schizophrenia Spectrum & Other Psychotic Disorders (200–)
    case SCHIZOTYPAL_PERSONALITY_DISORDER = 200;
    case DELUSIONAL_DISORDER = 201;
    case BRIEF_PSYCHOTIC_DISORDER = 202;
    case SCHIZOPHRENIFORM_DISORDER = 203;
    case SCHIZOPHRENIA = 204;
    case SCHIZOAFFECTIVE_DISORDER_BIPOLAR_TYPE = 205;
    case SCHIZOAFFECTIVE_DISORDER_DEPRESSIVE_TYPE = 206;
    case SUBSTANCE_INDUCED_PSYCHOTIC_DISORDER = 207;
    case PSYCHOTIC_DISORDER_DUE_TO_MEDICAL_CONDITION = 208;
    case CATATONIA_ASSOCIATED_WITH_MENTAL_DISORDER = 209;

    // Bipolar & Related Disorders (300–)
    case BIPOLAR_I_DISORDER = 300;
    case BIPOLAR_II_DISORDER = 301;
    case CYCLOTHYMIC_DISORDER = 302;
    case SUBSTANCE_INDUCED_BIPOLAR_DISORDER = 303;
    case BIPOLAR_DISORDER_DUE_TO_MEDICAL_CONDITION = 304;

    // Depressive Disorders (400–)
    case DISRUPTIVE_MOOD_DYSREGULATION_DISORDER = 400;
    case MAJOR_DEPRESSIVE_DISORDER_SINGLE_EPISODE = 401;
    case MAJOR_DEPRESSIVE_DISORDER_RECURRENT = 402;
    case PERSISTENT_DEPRESSIVE_DISORDER = 403;
    case PREMENSTRUAL_DYSPHORIC_DISORDER = 404;
    case SUBSTANCE_INDUCED_DEPRESSIVE_DISORDER = 405;
    case DEPRESSIVE_DISORDER_DUE_TO_MEDICAL_CONDITION = 406;

    // Anxiety Disorders (500–)
    case SEPARATION_ANXIETY_DISORDER = 500;
    case SELECTIVE_MUTISM = 501;
    case SPECIFIC_PHOBIA = 502;
    case SOCIAL_ANXIETY_DISORDER = 503;
    case PANIC_DISORDER = 504;
    case AGORAPHOBIA = 505;
    case GENERALIZED_ANXIETY_DISORDER = 506;
    case SUBSTANCE_INDUCED_ANXIETY_DISORDER = 507;
    case ANXIETY_DISORDER_DUE_TO_MEDICAL_CONDITION = 508;

    // Obsessive-Compulsive & Related Disorders (600–)
    case OBSESSIVE_COMPULSIVE_DISORDER = 600;
    case BODY_DYSMORPHIC_DISORDER = 601;
    case HOARDING_DISORDER = 602;
    case TRICHOTILLOMANIA = 603;
    case EXCORIATION_DISORDER = 604;
    case SUBSTANCE_INDUCED_OCD = 605;
    case OCD_DUE_TO_MEDICAL_CONDITION = 606;

    // Trauma & Stressor-Related Disorders (700–)
    case REACTIVE_ATTACHMENT_DISORDER = 700;
    case DISINHIBITED_SOCIAL_ENGAGEMENT_DISORDER = 701;
    case POSTTRAUMATIC_STRESS_DISORDER = 702;
    case ACUTE_STRESS_DISORDER = 703;
    case ADJUSTMENT_DISORDER_WITH_DEPRESSED_MOOD = 704;
    case ADJUSTMENT_DISORDER_WITH_ANXIETY = 705;
    case ADJUSTMENT_DISORDER_WITH_MIXED_FEATURES = 706;
    case ADJUSTMENT_DISORDER_WITH_DISTURBANCE_OF_CONDUCT = 707;
    case ADJUSTMENT_DISORDER_WITH_MIXED_DISTURBANCE = 708;
    case PROLONGED_GRIEF_DISORDER = 709;

    // Dissociative Disorders (800–)
    case DISSOCIATIVE_IDENTITY_DISORDER = 800;
    case DISSOCIATIVE_AMNESIA = 801;
    case DEPERSONALIZATION_DEREALIZATION_DISORDER = 802;

    // Somatic Symptom & Related Disorders (900–)
    case SOMATIC_SYMPTOM_DISORDER = 900;
    case ILLNESS_ANXIETY_DISORDER = 901;
    case CONVERSION_DISORDER = 902;
    case PSYCHOLOGICAL_FACTORS_AFFECTING_MEDICAL_CONDITION = 903;
    case FACTITIOUS_DISORDER = 904;

    // Feeding & Eating Disorders (1000–)
    case PICA = 1000;
    case RUMINATION_DISORDER = 1001;
    case AVOIDANT_RESTRICTIVE_FOOD_INTAKE_DISORDER = 1002;
    case ANOREXIA_NERVOSA_RESTRICTING_TYPE = 1003;
    case ANOREXIA_NERVOSA_BINGE_PURGE_TYPE = 1004;
    case BULIMIA_NERVOSA = 1005;
    case BINGE_EATING_DISORDER = 1006;

    // Elimination Disorders (1100–)
    case ENURESIS = 1100;
    case ENCOPRESIS = 1101;

    // Sleep-Wake Disorders (1200–)
    case INSOMNIA_DISORDER = 1200;
    case HYPERSOMNOLENCE_DISORDER = 1201;
    case NARCOLEPSY = 1202;
    case OBSTRUCTIVE_SLEEP_APNEA_HYPOPNEA = 1203;
    case CENTRAL_SLEEP_APNEA = 1204;
    case SLEEP_RELATED_HYPOVENTILATION = 1205;
    case CIRCADIAN_RHYTHM_SLEEP_WAKE_DISORDER = 1206;
    case NON_REM_SLEEP_AROUSAL_DISORDER_SLEEPWALKING = 1207;
    case NON_REM_SLEEP_AROUSAL_DISORDER_SLEEP_TERRORS = 1208;
    case NIGHTMARE_DISORDER = 1209;
    case REM_SLEEP_BEHAVIOR_DISORDER = 1210;
    case RESTLESS_LEGS_SYNDROME = 1211;

    // Sexual Dysfunctions (1300–)
    case DELAYED_EJACULATION = 1300;
    case ERECTILE_DISORDER = 1301;
    case FEMALE_ORGASMIC_DISORDER = 1302;
    case FEMALE_SEXUAL_INTEREST_AROUSAL_DISORDER = 1303;
    case GENITO_PELVIC_PAIN_PENETRATION_DISORDER = 1304;
    case MALE_HYPOACTIVE_SEXUAL_DESIRE_DISORDER = 1305;
    case PREMATURE_EJACULATION = 1306;
    case SUBSTANCE_INDUCED_SEXUAL_DYSFUNCTION = 1307;

    // Gender Dysphoria (1400–)
    case GENDER_DYSPHORIA_IN_CHILDREN = 1400;
    case GENDER_DYSPHORIA_IN_ADOLESCENTS_ADULTS = 1401;

    // Disruptive, Impulse-Control & Conduct Disorders (1500–)
    case OPPOSITIONAL_DEFIANT_DISORDER = 1500;
    case INTERMITTENT_EXPLOSIVE_DISORDER = 1501;
    case CONDUCT_DISORDER_CHILDHOOD_ONSET = 1502;
    case CONDUCT_DISORDER_ADOLESCENT_ONSET = 1503;
    case ANTISOCIAL_PERSONALITY_DISORDER = 1504;
    case PYROMANIA = 1505;
    case KLEPTOMANIA = 1506;

    // Substance-Related & Addictive Disorders (1600–)
    case ALCOHOL_USE_DISORDER = 1600;
    case ALCOHOL_INTOXICATION = 1601;
    case ALCOHOL_WITHDRAWAL = 1602;
    case CAFFEINE_INTOXICATION = 1603;
    case CAFFEINE_WITHDRAWAL = 1604;
    case CANNABIS_USE_DISORDER = 1605;
    case CANNABIS_INTOXICATION = 1606;
    case CANNABIS_WITHDRAWAL = 1607;
    case PHENCYCLIDINE_USE_DISORDER = 1608;
    case OTHER_HALLUCINOGEN_USE_DISORDER = 1609;
    case INHALANT_USE_DISORDER = 1610;
    case OPIOID_USE_DISORDER = 1611;
    case OPIOID_INTOXICATION = 1612;
    case OPIOID_WITHDRAWAL = 1613;
    case SEDATIVE_HYPNOTIC_ANXIOLYTIC_USE_DISORDER = 1614;
    case STIMULANT_USE_DISORDER_AMPHETAMINE = 1615;
    case STIMULANT_USE_DISORDER_COCAINE = 1616;
    case TOBACCO_USE_DISORDER = 1617;
    case TOBACCO_WITHDRAWAL = 1618;
    case GAMBLING_DISORDER = 1619;

    // Neurocognitive Disorders (1700–)
    case DELIRIUM = 1700;
    case MAJOR_NEUROCOGNITIVE_DISORDER_ALZHEIMERS = 1701;
    case MAJOR_NEUROCOGNITIVE_DISORDER_VASCULAR = 1702;
    case MAJOR_NEUROCOGNITIVE_DISORDER_LEWY_BODY = 1703;
    case MAJOR_NEUROCOGNITIVE_DISORDER_PARKINSONS = 1704;
    case MAJOR_NEUROCOGNITIVE_DISORDER_FRONTOTEMPORAL = 1705;
    case MAJOR_NEUROCOGNITIVE_DISORDER_TBI = 1706;
    case MAJOR_NEUROCOGNITIVE_DISORDER_HIV = 1707;
    case MAJOR_NEUROCOGNITIVE_DISORDER_SUBSTANCE_INDUCED = 1708;
    case MAJOR_NEUROCOGNITIVE_DISORDER_HUNTINGTONS = 1709;
    case MILD_NEUROCOGNITIVE_DISORDER = 1710;

    // Personality Disorders (1800–)
    case PARANOID_PERSONALITY_DISORDER = 1800;
    case SCHIZOID_PERSONALITY_DISORDER = 1801;
    case HISTRIONIC_PERSONALITY_DISORDER = 1802;
    case NARCISSISTIC_PERSONALITY_DISORDER = 1803;
    case BORDERLINE_PERSONALITY_DISORDER = 1804;
    case AVOIDANT_PERSONALITY_DISORDER = 1805;
    case DEPENDENT_PERSONALITY_DISORDER = 1806;
    case OBSESSIVE_COMPULSIVE_PERSONALITY_DISORDER = 1807;

    // Paraphilic Disorders (1900–)
    case VOYEURISTIC_DISORDER = 1900;
    case EXHIBITIONISTIC_DISORDER = 1901;
    case FROTTEURISTIC_DISORDER = 1902;
    case SEXUAL_MASOCHISM_DISORDER = 1903;
    case SEXUAL_SADISM_DISORDER = 1904;
    case PEDOPHILIC_DISORDER = 1905;
    case FETISHISTIC_DISORDER = 1906;
    case TRANSVESTIC_DISORDER = 1907;

    public function label(): string
    {
        return match ($this) {
            self::INTELLECTUAL_DISABILITY_MILD => 'Intellectual Disability — Mild',
            self::INTELLECTUAL_DISABILITY_MODERATE => 'Intellectual Disability — Moderate',
            self::INTELLECTUAL_DISABILITY_SEVERE => 'Intellectual Disability — Severe',
            self::INTELLECTUAL_DISABILITY_PROFOUND => 'Intellectual Disability — Profound',
            self::GLOBAL_DEVELOPMENTAL_DELAY => 'Global Developmental Delay',
            self::AUTISM_SPECTRUM_DISORDER => 'Autism Spectrum Disorder',
            self::ADHD_COMBINED => 'ADHD — Combined Presentation',
            self::ADHD_PREDOMINANTLY_INATTENTIVE => 'ADHD — Predominantly Inattentive',
            self::ADHD_PREDOMINANTLY_HYPERACTIVE_IMPULSIVE => 'ADHD — Predominantly Hyperactive-Impulsive',
            self::SPECIFIC_LEARNING_DISORDER_READING => 'Specific Learning Disorder — Reading',
            self::SPECIFIC_LEARNING_DISORDER_WRITTEN_EXPRESSION => 'Specific Learning Disorder — Written Expression',
            self::SPECIFIC_LEARNING_DISORDER_MATHEMATICS => 'Specific Learning Disorder — Mathematics',
            self::DEVELOPMENTAL_COORDINATION_DISORDER => 'Developmental Coordination Disorder',
            self::STEREOTYPIC_MOVEMENT_DISORDER => 'Stereotypic Movement Disorder',
            self::TOURETTE_SYNDROME => 'Tourette\'s Disorder',
            self::PERSISTENT_MOTOR_VOCAL_TIC_DISORDER => 'Persistent Motor or Vocal Tic Disorder',
            self::PROVISIONAL_TIC_DISORDER => 'Provisional Tic Disorder',
            self::LANGUAGE_DISORDER => 'Language Disorder',
            self::SPEECH_SOUND_DISORDER => 'Speech Sound Disorder',
            self::CHILDHOOD_ONSET_FLUENCY_DISORDER => 'Childhood-Onset Fluency Disorder (Stuttering)',
            self::SOCIAL_PRAGMATIC_COMMUNICATION_DISORDER => 'Social (Pragmatic) Communication Disorder',
            self::SCHIZOTYPAL_PERSONALITY_DISORDER => 'Schizotypal Personality Disorder',
            self::DELUSIONAL_DISORDER => 'Delusional Disorder',
            self::BRIEF_PSYCHOTIC_DISORDER => 'Brief Psychotic Disorder',
            self::SCHIZOPHRENIFORM_DISORDER => 'Schizophreniform Disorder',
            self::SCHIZOPHRENIA => 'Schizophrenia',
            self::SCHIZOAFFECTIVE_DISORDER_BIPOLAR_TYPE => 'Schizoaffective Disorder — Bipolar Type',
            self::SCHIZOAFFECTIVE_DISORDER_DEPRESSIVE_TYPE => 'Schizoaffective Disorder — Depressive Type',
            self::SUBSTANCE_INDUCED_PSYCHOTIC_DISORDER => 'Substance/Medication-Induced Psychotic Disorder',
            self::PSYCHOTIC_DISORDER_DUE_TO_MEDICAL_CONDITION => 'Psychotic Disorder Due to Another Medical Condition',
            self::CATATONIA_ASSOCIATED_WITH_MENTAL_DISORDER => 'Catatonia Associated with Another Mental Disorder',
            self::BIPOLAR_I_DISORDER => 'Bipolar I Disorder',
            self::BIPOLAR_II_DISORDER => 'Bipolar II Disorder',
            self::CYCLOTHYMIC_DISORDER => 'Cyclothymic Disorder',
            self::SUBSTANCE_INDUCED_BIPOLAR_DISORDER => 'Substance/Medication-Induced Bipolar Disorder',
            self::BIPOLAR_DISORDER_DUE_TO_MEDICAL_CONDITION => 'Bipolar Disorder Due to Another Medical Condition',
            self::DISRUPTIVE_MOOD_DYSREGULATION_DISORDER => 'Disruptive Mood Dysregulation Disorder',
            self::MAJOR_DEPRESSIVE_DISORDER_SINGLE_EPISODE => 'Major Depressive Disorder — Single Episode',
            self::MAJOR_DEPRESSIVE_DISORDER_RECURRENT => 'Major Depressive Disorder — Recurrent',
            self::PERSISTENT_DEPRESSIVE_DISORDER => 'Persistent Depressive Disorder (Dysthymia)',
            self::PREMENSTRUAL_DYSPHORIC_DISORDER => 'Premenstrual Dysphoric Disorder',
            self::SUBSTANCE_INDUCED_DEPRESSIVE_DISORDER => 'Substance/Medication-Induced Depressive Disorder',
            self::DEPRESSIVE_DISORDER_DUE_TO_MEDICAL_CONDITION => 'Depressive Disorder Due to Another Medical Condition',
            self::SEPARATION_ANXIETY_DISORDER => 'Separation Anxiety Disorder',
            self::SELECTIVE_MUTISM => 'Selective Mutism',
            self::SPECIFIC_PHOBIA => 'Specific Phobia',
            self::SOCIAL_ANXIETY_DISORDER => 'Social Anxiety Disorder (Social Phobia)',
            self::PANIC_DISORDER => 'Panic Disorder',
            self::AGORAPHOBIA => 'Agoraphobia',
            self::GENERALIZED_ANXIETY_DISORDER => 'Generalized Anxiety Disorder',
            self::SUBSTANCE_INDUCED_ANXIETY_DISORDER => 'Substance/Medication-Induced Anxiety Disorder',
            self::ANXIETY_DISORDER_DUE_TO_MEDICAL_CONDITION => 'Anxiety Disorder Due to Another Medical Condition',
            self::OBSESSIVE_COMPULSIVE_DISORDER => 'Obsessive-Compulsive Disorder',
            self::BODY_DYSMORPHIC_DISORDER => 'Body Dysmorphic Disorder',
            self::HOARDING_DISORDER => 'Hoarding Disorder',
            self::TRICHOTILLOMANIA => 'Trichotillomania (Hair-Pulling Disorder)',
            self::EXCORIATION_DISORDER => 'Excoriation (Skin-Picking) Disorder',
            self::SUBSTANCE_INDUCED_OCD => 'Substance/Medication-Induced OCD-Related Disorder',
            self::OCD_DUE_TO_MEDICAL_CONDITION => 'OCD-Related Disorder Due to Another Medical Condition',
            self::REACTIVE_ATTACHMENT_DISORDER => 'Reactive Attachment Disorder',
            self::DISINHIBITED_SOCIAL_ENGAGEMENT_DISORDER => 'Disinhibited Social Engagement Disorder',
            self::POSTTRAUMATIC_STRESS_DISORDER => 'Posttraumatic Stress Disorder',
            self::ACUTE_STRESS_DISORDER => 'Acute Stress Disorder',
            self::ADJUSTMENT_DISORDER_WITH_DEPRESSED_MOOD => 'Adjustment Disorder with Depressed Mood',
            self::ADJUSTMENT_DISORDER_WITH_ANXIETY => 'Adjustment Disorder with Anxiety',
            self::ADJUSTMENT_DISORDER_WITH_MIXED_FEATURES => 'Adjustment Disorder with Mixed Anxiety and Depressed Mood',
            self::ADJUSTMENT_DISORDER_WITH_DISTURBANCE_OF_CONDUCT => 'Adjustment Disorder with Disturbance of Conduct',
            self::ADJUSTMENT_DISORDER_WITH_MIXED_DISTURBANCE => 'Adjustment Disorder with Mixed Disturbance of Emotions and Conduct',
            self::PROLONGED_GRIEF_DISORDER => 'Prolonged Grief Disorder',
            self::DISSOCIATIVE_IDENTITY_DISORDER => 'Dissociative Identity Disorder',
            self::DISSOCIATIVE_AMNESIA => 'Dissociative Amnesia',
            self::DEPERSONALIZATION_DEREALIZATION_DISORDER => 'Depersonalization/Derealization Disorder',
            self::SOMATIC_SYMPTOM_DISORDER => 'Somatic Symptom Disorder',
            self::ILLNESS_ANXIETY_DISORDER => 'Illness Anxiety Disorder',
            self::CONVERSION_DISORDER => 'Conversion Disorder (Functional Neurological Symptom Disorder)',
            self::PSYCHOLOGICAL_FACTORS_AFFECTING_MEDICAL_CONDITION => 'Psychological Factors Affecting Other Medical Conditions',
            self::FACTITIOUS_DISORDER => 'Factitious Disorder',
            self::PICA => 'Pica',
            self::RUMINATION_DISORDER => 'Rumination Disorder',
            self::AVOIDANT_RESTRICTIVE_FOOD_INTAKE_DISORDER => 'Avoidant/Restrictive Food Intake Disorder',
            self::ANOREXIA_NERVOSA_RESTRICTING_TYPE => 'Anorexia Nervosa — Restricting Type',
            self::ANOREXIA_NERVOSA_BINGE_PURGE_TYPE => 'Anorexia Nervosa — Binge-Eating/Purging Type',
            self::BULIMIA_NERVOSA => 'Bulimia Nervosa',
            self::BINGE_EATING_DISORDER => 'Binge-Eating Disorder',
            self::ENURESIS => 'Enuresis',
            self::ENCOPRESIS => 'Encopresis',
            self::INSOMNIA_DISORDER => 'Insomnia Disorder',
            self::HYPERSOMNOLENCE_DISORDER => 'Hypersomnolence Disorder',
            self::NARCOLEPSY => 'Narcolepsy',
            self::OBSTRUCTIVE_SLEEP_APNEA_HYPOPNEA => 'Obstructive Sleep Apnea Hypopnea',
            self::CENTRAL_SLEEP_APNEA => 'Central Sleep Apnea',
            self::SLEEP_RELATED_HYPOVENTILATION => 'Sleep-Related Hypoventilation',
            self::CIRCADIAN_RHYTHM_SLEEP_WAKE_DISORDER => 'Circadian Rhythm Sleep-Wake Disorder',
            self::NON_REM_SLEEP_AROUSAL_DISORDER_SLEEPWALKING => 'Non-REM Sleep Arousal Disorder — Sleepwalking Type',
            self::NON_REM_SLEEP_AROUSAL_DISORDER_SLEEP_TERRORS => 'Non-REM Sleep Arousal Disorder — Sleep Terror Type',
            self::NIGHTMARE_DISORDER => 'Nightmare Disorder',
            self::REM_SLEEP_BEHAVIOR_DISORDER => 'REM Sleep Behavior Disorder',
            self::RESTLESS_LEGS_SYNDROME => 'Restless Legs Syndrome',
            self::DELAYED_EJACULATION => 'Delayed Ejaculation',
            self::ERECTILE_DISORDER => 'Erectile Disorder',
            self::FEMALE_ORGASMIC_DISORDER => 'Female Orgasmic Disorder',
            self::FEMALE_SEXUAL_INTEREST_AROUSAL_DISORDER => 'Female Sexual Interest/Arousal Disorder',
            self::GENITO_PELVIC_PAIN_PENETRATION_DISORDER => 'Genito-Pelvic Pain/Penetration Disorder',
            self::MALE_HYPOACTIVE_SEXUAL_DESIRE_DISORDER => 'Male Hypoactive Sexual Desire Disorder',
            self::PREMATURE_EJACULATION => 'Premature (Early) Ejaculation',
            self::SUBSTANCE_INDUCED_SEXUAL_DYSFUNCTION => 'Substance/Medication-Induced Sexual Dysfunction',
            self::GENDER_DYSPHORIA_IN_CHILDREN => 'Gender Dysphoria in Children',
            self::GENDER_DYSPHORIA_IN_ADOLESCENTS_ADULTS => 'Gender Dysphoria in Adolescents and Adults',
            self::OPPOSITIONAL_DEFIANT_DISORDER => 'Oppositional Defiant Disorder',
            self::INTERMITTENT_EXPLOSIVE_DISORDER => 'Intermittent Explosive Disorder',
            self::CONDUCT_DISORDER_CHILDHOOD_ONSET => 'Conduct Disorder — Childhood-Onset Type',
            self::CONDUCT_DISORDER_ADOLESCENT_ONSET => 'Conduct Disorder — Adolescent-Onset Type',
            self::ANTISOCIAL_PERSONALITY_DISORDER => 'Antisocial Personality Disorder',
            self::PYROMANIA => 'Pyromania',
            self::KLEPTOMANIA => 'Kleptomania',
            self::ALCOHOL_USE_DISORDER => 'Alcohol Use Disorder',
            self::ALCOHOL_INTOXICATION => 'Alcohol Intoxication',
            self::ALCOHOL_WITHDRAWAL => 'Alcohol Withdrawal',
            self::CAFFEINE_INTOXICATION => 'Caffeine Intoxication',
            self::CAFFEINE_WITHDRAWAL => 'Caffeine Withdrawal',
            self::CANNABIS_USE_DISORDER => 'Cannabis Use Disorder',
            self::CANNABIS_INTOXICATION => 'Cannabis Intoxication',
            self::CANNABIS_WITHDRAWAL => 'Cannabis Withdrawal',
            self::PHENCYCLIDINE_USE_DISORDER => 'Phencyclidine Use Disorder',
            self::OTHER_HALLUCINOGEN_USE_DISORDER => 'Other Hallucinogen Use Disorder',
            self::INHALANT_USE_DISORDER => 'Inhalant Use Disorder',
            self::OPIOID_USE_DISORDER => 'Opioid Use Disorder',
            self::OPIOID_INTOXICATION => 'Opioid Intoxication',
            self::OPIOID_WITHDRAWAL => 'Opioid Withdrawal',
            self::SEDATIVE_HYPNOTIC_ANXIOLYTIC_USE_DISORDER => 'Sedative, Hypnotic, or Anxiolytic Use Disorder',
            self::STIMULANT_USE_DISORDER_AMPHETAMINE => 'Stimulant Use Disorder — Amphetamine-Type',
            self::STIMULANT_USE_DISORDER_COCAINE => 'Stimulant Use Disorder — Cocaine',
            self::TOBACCO_USE_DISORDER => 'Tobacco Use Disorder',
            self::TOBACCO_WITHDRAWAL => 'Tobacco Withdrawal',
            self::GAMBLING_DISORDER => 'Gambling Disorder',
            self::DELIRIUM => 'Delirium',
            self::MAJOR_NEUROCOGNITIVE_DISORDER_ALZHEIMERS => 'Major Neurocognitive Disorder Due to Alzheimer\'s Disease',
            self::MAJOR_NEUROCOGNITIVE_DISORDER_VASCULAR => 'Major Vascular Neurocognitive Disorder',
            self::MAJOR_NEUROCOGNITIVE_DISORDER_LEWY_BODY => 'Major Neurocognitive Disorder with Lewy Bodies',
            self::MAJOR_NEUROCOGNITIVE_DISORDER_PARKINSONS => 'Major Neurocognitive Disorder Due to Parkinson\'s Disease',
            self::MAJOR_NEUROCOGNITIVE_DISORDER_FRONTOTEMPORAL => 'Major Frontotemporal Neurocognitive Disorder',
            self::MAJOR_NEUROCOGNITIVE_DISORDER_TBI => 'Major Neurocognitive Disorder Due to Traumatic Brain Injury',
            self::MAJOR_NEUROCOGNITIVE_DISORDER_HIV => 'Major Neurocognitive Disorder Due to HIV Infection',
            self::MAJOR_NEUROCOGNITIVE_DISORDER_SUBSTANCE_INDUCED => 'Substance/Medication-Induced Major Neurocognitive Disorder',
            self::MAJOR_NEUROCOGNITIVE_DISORDER_HUNTINGTONS => 'Major Neurocognitive Disorder Due to Huntington\'s Disease',
            self::MILD_NEUROCOGNITIVE_DISORDER => 'Mild Neurocognitive Disorder',
            self::PARANOID_PERSONALITY_DISORDER => 'Paranoid Personality Disorder',
            self::SCHIZOID_PERSONALITY_DISORDER => 'Schizoid Personality Disorder',
            self::HISTRIONIC_PERSONALITY_DISORDER => 'Histrionic Personality Disorder',
            self::NARCISSISTIC_PERSONALITY_DISORDER => 'Narcissistic Personality Disorder',
            self::BORDERLINE_PERSONALITY_DISORDER => 'Borderline Personality Disorder',
            self::AVOIDANT_PERSONALITY_DISORDER => 'Avoidant Personality Disorder',
            self::DEPENDENT_PERSONALITY_DISORDER => 'Dependent Personality Disorder',
            self::OBSESSIVE_COMPULSIVE_PERSONALITY_DISORDER => 'Obsessive-Compulsive Personality Disorder',
            self::VOYEURISTIC_DISORDER => 'Voyeuristic Disorder',
            self::EXHIBITIONISTIC_DISORDER => 'Exhibitionistic Disorder',
            self::FROTTEURISTIC_DISORDER => 'Frotteuristic Disorder',
            self::SEXUAL_MASOCHISM_DISORDER => 'Sexual Masochism Disorder',
            self::SEXUAL_SADISM_DISORDER => 'Sexual Sadism Disorder',
            self::PEDOPHILIC_DISORDER => 'Pedophilic Disorder',
            self::FETISHISTIC_DISORDER => 'Fetishistic Disorder',
            self::TRANSVESTIC_DISORDER => 'Transvestic Disorder',
        };
    }

    public function category(): string
    {
        return match (true) {
            $this->value >= 100 && $this->value < 200 => 'Neurodevelopmental Disorders',
            $this->value >= 200 && $this->value < 300 => 'Schizophrenia Spectrum and Other Psychotic Disorders',
            $this->value >= 300 && $this->value < 400 => 'Bipolar and Related Disorders',
            $this->value >= 400 && $this->value < 500 => 'Depressive Disorders',
            $this->value >= 500 && $this->value < 600 => 'Anxiety Disorders',
            $this->value >= 600 && $this->value < 700 => 'Obsessive-Compulsive and Related Disorders',
            $this->value >= 700 && $this->value < 800 => 'Trauma- and Stressor-Related Disorders',
            $this->value >= 800 && $this->value < 900 => 'Dissociative Disorders',
            $this->value >= 900 && $this->value < 1000 => 'Somatic Symptom and Related Disorders',
            $this->value >= 1000 && $this->value < 1100 => 'Feeding and Eating Disorders',
            $this->value >= 1100 && $this->value < 1200 => 'Elimination Disorders',
            $this->value >= 1200 && $this->value < 1300 => 'Sleep-Wake Disorders',
            $this->value >= 1300 && $this->value < 1400 => 'Sexual Dysfunctions',
            $this->value >= 1400 && $this->value < 1500 => 'Gender Dysphoria',
            $this->value >= 1500 && $this->value < 1600 => 'Disruptive, Impulse-Control, and Conduct Disorders',
            $this->value >= 1600 && $this->value < 1700 => 'Substance-Related and Addictive Disorders',
            $this->value >= 1700 && $this->value < 1800 => 'Neurocognitive Disorders',
            $this->value >= 1800 && $this->value < 1900 => 'Personality Disorders',
            $this->value >= 1900 => 'Paraphilic Disorders',
        };
    }

    public function isSubstanceInduced(): bool
    {
        return in_array($this, [
            self::SUBSTANCE_INDUCED_PSYCHOTIC_DISORDER,
            self::SUBSTANCE_INDUCED_BIPOLAR_DISORDER,
            self::SUBSTANCE_INDUCED_DEPRESSIVE_DISORDER,
            self::SUBSTANCE_INDUCED_ANXIETY_DISORDER,
            self::SUBSTANCE_INDUCED_OCD,
            self::SUBSTANCE_INDUCED_SEXUAL_DYSFUNCTION,
            self::MAJOR_NEUROCOGNITIVE_DISORDER_SUBSTANCE_INDUCED,
            self::ALCOHOL_INTOXICATION,
            self::ALCOHOL_WITHDRAWAL,
            self::CAFFEINE_INTOXICATION,
            self::CAFFEINE_WITHDRAWAL,
            self::CANNABIS_INTOXICATION,
            self::CANNABIS_WITHDRAWAL,
            self::OPIOID_INTOXICATION,
            self::OPIOID_WITHDRAWAL,
            self::TOBACCO_WITHDRAWAL,
        ], true);
    }

    public function isPsychotic(): bool
    {
        return $this->value >= 200 && $this->value < 300;
    }

    public function isMoodDisorder(): bool
    {
        return ($this->value >= 300 && $this->value < 500);
    }

    /** @return list<self> */
    public static function byCategory(string $category): array
    {
        return array_values(
            array_filter(self::cases(), fn(self $case) => $case->category() === $category)
        );
    }
}
