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

enum BloodVessel: string
{
    // -------------------------------------------------------------------------
    // Cerebral Arteries
    // -------------------------------------------------------------------------
    case INTERNAL_CAROTID_ARTERY_LEFT = 'internal_carotid_artery_left';
    case INTERNAL_CAROTID_ARTERY_RIGHT = 'internal_carotid_artery_right';
    case ANTERIOR_CEREBRAL_ARTERY_LEFT = 'anterior_cerebral_artery_left';
    case ANTERIOR_CEREBRAL_ARTERY_RIGHT = 'anterior_cerebral_artery_right';
    case MIDDLE_CEREBRAL_ARTERY_LEFT = 'middle_cerebral_artery_left';
    case MIDDLE_CEREBRAL_ARTERY_RIGHT = 'middle_cerebral_artery_right';
    case POSTERIOR_CEREBRAL_ARTERY_LEFT = 'posterior_cerebral_artery_left';
    case POSTERIOR_CEREBRAL_ARTERY_RIGHT = 'posterior_cerebral_artery_right';
    case ANTERIOR_COMMUNICATING_ARTERY = 'anterior_communicating_artery';
    case POSTERIOR_COMMUNICATING_ARTERY_LEFT = 'posterior_communicating_artery_left';
    case POSTERIOR_COMMUNICATING_ARTERY_RIGHT = 'posterior_communicating_artery_right';
    case BASILAR_ARTERY = 'basilar_artery';
    case VERTEBRAL_ARTERY_LEFT = 'vertebral_artery_left';
    case VERTEBRAL_ARTERY_RIGHT = 'vertebral_artery_right';
    case POSTERIOR_INFERIOR_CEREBELLAR_ARTERY_LEFT = 'posterior_inferior_cerebellar_artery_left';
    case POSTERIOR_INFERIOR_CEREBELLAR_ARTERY_RIGHT = 'posterior_inferior_cerebellar_artery_right';
    case ANTERIOR_INFERIOR_CEREBELLAR_ARTERY_LEFT = 'anterior_inferior_cerebellar_artery_left';
    case ANTERIOR_INFERIOR_CEREBELLAR_ARTERY_RIGHT = 'anterior_inferior_cerebellar_artery_right';
    case SUPERIOR_CEREBELLAR_ARTERY_LEFT = 'superior_cerebellar_artery_left';
    case SUPERIOR_CEREBELLAR_ARTERY_RIGHT = 'superior_cerebellar_artery_right';
    case OPHTHALMIC_ARTERY_LEFT = 'ophthalmic_artery_left';
    case OPHTHALMIC_ARTERY_RIGHT = 'ophthalmic_artery_right';
    case LENTICULOSTRIATE_ARTERY_LEFT = 'lenticulostriate_artery_left';
    case LENTICULOSTRIATE_ARTERY_RIGHT = 'lenticulostriate_artery_right';
    case PERICALLOSAL_ARTERY_LEFT = 'pericallosal_artery_left';
    case PERICALLOSAL_ARTERY_RIGHT = 'pericallosal_artery_right';
    case CALLOSOMARGINAL_ARTERY_LEFT = 'callosomarginal_artery_left';
    case CALLOSOMARGINAL_ARTERY_RIGHT = 'callosomarginal_artery_right';

    // -------------------------------------------------------------------------
    // Cerebral Veins & Sinuses
    // -------------------------------------------------------------------------
    case SUPERIOR_SAGITTAL_SINUS = 'superior_sagittal_sinus';
    case INFERIOR_SAGITTAL_SINUS = 'inferior_sagittal_sinus';
    case STRAIGHT_SINUS = 'straight_sinus';
    case TRANSVERSE_SINUS_LEFT = 'transverse_sinus_left';
    case TRANSVERSE_SINUS_RIGHT = 'transverse_sinus_right';
    case SIGMOID_SINUS_LEFT = 'sigmoid_sinus_left';
    case SIGMOID_SINUS_RIGHT = 'sigmoid_sinus_right';
    case CAVERNOUS_SINUS_LEFT = 'cavernous_sinus_left';
    case CAVERNOUS_SINUS_RIGHT = 'cavernous_sinus_right';
    case GREAT_CEREBRAL_VEIN_OF_GALEN = 'great_cerebral_vein_of_galen';
    case INTERNAL_CEREBRAL_VEIN_LEFT = 'internal_cerebral_vein_left';
    case INTERNAL_CEREBRAL_VEIN_RIGHT = 'internal_cerebral_vein_right';
    case BASAL_VEIN_OF_ROSENTHAL_LEFT = 'basal_vein_of_rosenthal_left';
    case BASAL_VEIN_OF_ROSENTHAL_RIGHT = 'basal_vein_of_rosenthal_right';
    case SUPERIOR_PETROSAL_SINUS_LEFT = 'superior_petrosal_sinus_left';
    case SUPERIOR_PETROSAL_SINUS_RIGHT = 'superior_petrosal_sinus_right';
    case INFERIOR_PETROSAL_SINUS_LEFT = 'inferior_petrosal_sinus_left';
    case INFERIOR_PETROSAL_SINUS_RIGHT = 'inferior_petrosal_sinus_right';
    case OCCIPITAL_SINUS = 'occipital_sinus';
    case INTERNAL_JUGULAR_VEIN_LEFT = 'internal_jugular_vein_left';
    case INTERNAL_JUGULAR_VEIN_RIGHT = 'internal_jugular_vein_right';

    // -------------------------------------------------------------------------
    // Coronary Arteries
    // -------------------------------------------------------------------------
    case LEFT_MAIN_CORONARY_ARTERY = 'left_main_coronary_artery';
    case LEFT_ANTERIOR_DESCENDING_ARTERY = 'left_anterior_descending_artery';
    case LEFT_CIRCUMFLEX_ARTERY = 'left_circumflex_artery';
    case RIGHT_CORONARY_ARTERY = 'right_coronary_artery';
    case POSTERIOR_DESCENDING_ARTERY = 'posterior_descending_artery';
    case OBTUSE_MARGINAL_ARTERY_FIRST = 'obtuse_marginal_artery_first';
    case OBTUSE_MARGINAL_ARTERY_SECOND = 'obtuse_marginal_artery_second';
    case DIAGONAL_BRANCH_FIRST = 'diagonal_branch_first';
    case DIAGONAL_BRANCH_SECOND = 'diagonal_branch_second';
    case SEPTAL_PERFORATOR_BRANCH = 'septal_perforator_branch';
    case SINOATRIAL_NODE_ARTERY = 'sinoatrial_node_artery';
    case ATRIOVENTRICULAR_NODE_ARTERY = 'atrioventricular_node_artery';
    case ACUTE_MARGINAL_ARTERY = 'acute_marginal_artery';
    case POSTEROLATERAL_BRANCH = 'posterolateral_branch';

    // -------------------------------------------------------------------------
    // Cardiac Veins
    // -------------------------------------------------------------------------
    case CORONARY_SINUS = 'coronary_sinus';
    case GREAT_CARDIAC_VEIN = 'great_cardiac_vein';
    case MIDDLE_CARDIAC_VEIN = 'middle_cardiac_vein';
    case SMALL_CARDIAC_VEIN = 'small_cardiac_vein';
    case ANTERIOR_CARDIAC_VEINS = 'anterior_cardiac_veins';
    case OBLIQUE_VEIN_OF_LEFT_ATRIUM = 'oblique_vein_of_left_atrium';
    case POSTERIOR_VEIN_OF_LEFT_VENTRICLE = 'posterior_vein_of_left_ventricle';

    // -------------------------------------------------------------------------
    // Aorta & Great Vessels
    // -------------------------------------------------------------------------
    case ASCENDING_AORTA = 'ascending_aorta';
    case AORTIC_ARCH = 'aortic_arch';
    case DESCENDING_THORACIC_AORTA = 'descending_thoracic_aorta';
    case ABDOMINAL_AORTA = 'abdominal_aorta';
    case BRACHIOCEPHALIC_TRUNK = 'brachiocephalic_trunk';
    case COMMON_CAROTID_ARTERY_LEFT = 'common_carotid_artery_left';
    case COMMON_CAROTID_ARTERY_RIGHT = 'common_carotid_artery_right';
    case SUBCLAVIAN_ARTERY_LEFT = 'subclavian_artery_left';
    case SUBCLAVIAN_ARTERY_RIGHT = 'subclavian_artery_right';

    // -------------------------------------------------------------------------
    // Pulmonary Vessels
    // -------------------------------------------------------------------------
    case PULMONARY_TRUNK = 'pulmonary_trunk';
    case PULMONARY_ARTERY_LEFT = 'pulmonary_artery_left';
    case PULMONARY_ARTERY_RIGHT = 'pulmonary_artery_right';
    case PULMONARY_VEIN_LEFT_SUPERIOR = 'pulmonary_vein_left_superior';
    case PULMONARY_VEIN_LEFT_INFERIOR = 'pulmonary_vein_left_inferior';
    case PULMONARY_VEIN_RIGHT_SUPERIOR = 'pulmonary_vein_right_superior';
    case PULMONARY_VEIN_RIGHT_INFERIOR = 'pulmonary_vein_right_inferior';

    // -------------------------------------------------------------------------
    // Systemic Veins (Caval)
    // -------------------------------------------------------------------------
    case SUPERIOR_VENA_CAVA = 'superior_vena_cava';
    case INFERIOR_VENA_CAVA = 'inferior_vena_cava';
    case AZYGOS_VEIN = 'azygos_vein';
    case HEMIAZYGOS_VEIN = 'hemiazygos_vein';
    case BRACHIOCEPHALIC_VEIN_LEFT = 'brachiocephalic_vein_left';
    case BRACHIOCEPHALIC_VEIN_RIGHT = 'brachiocephalic_vein_right';

    public function label(): string
    {
        return match ($this) {
            self::INTERNAL_CAROTID_ARTERY_LEFT => 'Internal Carotid Artery (Left)',
            self::INTERNAL_CAROTID_ARTERY_RIGHT => 'Internal Carotid Artery (Right)',
            self::ANTERIOR_CEREBRAL_ARTERY_LEFT => 'Anterior Cerebral Artery (Left)',
            self::ANTERIOR_CEREBRAL_ARTERY_RIGHT => 'Anterior Cerebral Artery (Right)',
            self::MIDDLE_CEREBRAL_ARTERY_LEFT => 'Middle Cerebral Artery (Left)',
            self::MIDDLE_CEREBRAL_ARTERY_RIGHT => 'Middle Cerebral Artery (Right)',
            self::POSTERIOR_CEREBRAL_ARTERY_LEFT => 'Posterior Cerebral Artery (Left)',
            self::POSTERIOR_CEREBRAL_ARTERY_RIGHT => 'Posterior Cerebral Artery (Right)',
            self::ANTERIOR_COMMUNICATING_ARTERY => 'Anterior Communicating Artery',
            self::POSTERIOR_COMMUNICATING_ARTERY_LEFT => 'Posterior Communicating Artery (Left)',
            self::POSTERIOR_COMMUNICATING_ARTERY_RIGHT => 'Posterior Communicating Artery (Right)',
            self::BASILAR_ARTERY => 'Basilar Artery',
            self::VERTEBRAL_ARTERY_LEFT => 'Vertebral Artery (Left)',
            self::VERTEBRAL_ARTERY_RIGHT => 'Vertebral Artery (Right)',
            self::POSTERIOR_INFERIOR_CEREBELLAR_ARTERY_LEFT => 'Posterior Inferior Cerebellar Artery — PICA (Left)',
            self::POSTERIOR_INFERIOR_CEREBELLAR_ARTERY_RIGHT => 'Posterior Inferior Cerebellar Artery — PICA (Right)',
            self::ANTERIOR_INFERIOR_CEREBELLAR_ARTERY_LEFT => 'Anterior Inferior Cerebellar Artery — AICA (Left)',
            self::ANTERIOR_INFERIOR_CEREBELLAR_ARTERY_RIGHT => 'Anterior Inferior Cerebellar Artery — AICA (Right)',
            self::SUPERIOR_CEREBELLAR_ARTERY_LEFT => 'Superior Cerebellar Artery (Left)',
            self::SUPERIOR_CEREBELLAR_ARTERY_RIGHT => 'Superior Cerebellar Artery (Right)',
            self::OPHTHALMIC_ARTERY_LEFT => 'Ophthalmic Artery (Left)',
            self::OPHTHALMIC_ARTERY_RIGHT => 'Ophthalmic Artery (Right)',
            self::LENTICULOSTRIATE_ARTERY_LEFT => 'Lenticulostriate Arteries (Left)',
            self::LENTICULOSTRIATE_ARTERY_RIGHT => 'Lenticulostriate Arteries (Right)',
            self::PERICALLOSAL_ARTERY_LEFT => 'Pericallosal Artery (Left)',
            self::PERICALLOSAL_ARTERY_RIGHT => 'Pericallosal Artery (Right)',
            self::CALLOSOMARGINAL_ARTERY_LEFT => 'Callosomarginal Artery (Left)',
            self::CALLOSOMARGINAL_ARTERY_RIGHT => 'Callosomarginal Artery (Right)',
            self::SUPERIOR_SAGITTAL_SINUS => 'Superior Sagittal Sinus',
            self::INFERIOR_SAGITTAL_SINUS => 'Inferior Sagittal Sinus',
            self::STRAIGHT_SINUS => 'Straight Sinus (Sinus Rectus)',
            self::TRANSVERSE_SINUS_LEFT => 'Transverse Sinus (Left)',
            self::TRANSVERSE_SINUS_RIGHT => 'Transverse Sinus (Right)',
            self::SIGMOID_SINUS_LEFT => 'Sigmoid Sinus (Left)',
            self::SIGMOID_SINUS_RIGHT => 'Sigmoid Sinus (Right)',
            self::CAVERNOUS_SINUS_LEFT => 'Cavernous Sinus (Left)',
            self::CAVERNOUS_SINUS_RIGHT => 'Cavernous Sinus (Right)',
            self::GREAT_CEREBRAL_VEIN_OF_GALEN => 'Great Cerebral Vein of Galen',
            self::INTERNAL_CEREBRAL_VEIN_LEFT => 'Internal Cerebral Vein (Left)',
            self::INTERNAL_CEREBRAL_VEIN_RIGHT => 'Internal Cerebral Vein (Right)',
            self::BASAL_VEIN_OF_ROSENTHAL_LEFT => 'Basal Vein of Rosenthal (Left)',
            self::BASAL_VEIN_OF_ROSENTHAL_RIGHT => 'Basal Vein of Rosenthal (Right)',
            self::SUPERIOR_PETROSAL_SINUS_LEFT => 'Superior Petrosal Sinus (Left)',
            self::SUPERIOR_PETROSAL_SINUS_RIGHT => 'Superior Petrosal Sinus (Right)',
            self::INFERIOR_PETROSAL_SINUS_LEFT => 'Inferior Petrosal Sinus (Left)',
            self::INFERIOR_PETROSAL_SINUS_RIGHT => 'Inferior Petrosal Sinus (Right)',
            self::OCCIPITAL_SINUS => 'Occipital Sinus',
            self::INTERNAL_JUGULAR_VEIN_LEFT => 'Internal Jugular Vein (Left)',
            self::INTERNAL_JUGULAR_VEIN_RIGHT => 'Internal Jugular Vein (Right)',
            self::LEFT_MAIN_CORONARY_ARTERY => 'Left Main Coronary Artery',
            self::LEFT_ANTERIOR_DESCENDING_ARTERY => 'Left Anterior Descending Artery — LAD',
            self::LEFT_CIRCUMFLEX_ARTERY => 'Left Circumflex Artery — LCx',
            self::RIGHT_CORONARY_ARTERY => 'Right Coronary Artery — RCA',
            self::POSTERIOR_DESCENDING_ARTERY => 'Posterior Descending Artery — PDA',
            self::OBTUSE_MARGINAL_ARTERY_FIRST => 'First Obtuse Marginal Artery — OM1',
            self::OBTUSE_MARGINAL_ARTERY_SECOND => 'Second Obtuse Marginal Artery — OM2',
            self::DIAGONAL_BRANCH_FIRST => 'First Diagonal Branch — D1',
            self::DIAGONAL_BRANCH_SECOND => 'Second Diagonal Branch — D2',
            self::SEPTAL_PERFORATOR_BRANCH => 'Septal Perforator Branch',
            self::SINOATRIAL_NODE_ARTERY => 'Sinoatrial Node Artery',
            self::ATRIOVENTRICULAR_NODE_ARTERY => 'Atrioventricular Node Artery',
            self::ACUTE_MARGINAL_ARTERY => 'Acute Marginal Artery',
            self::POSTEROLATERAL_BRANCH => 'Posterolateral Branch — PLB',
            self::CORONARY_SINUS => 'Coronary Sinus',
            self::GREAT_CARDIAC_VEIN => 'Great Cardiac Vein',
            self::MIDDLE_CARDIAC_VEIN => 'Middle Cardiac Vein',
            self::SMALL_CARDIAC_VEIN => 'Small Cardiac Vein',
            self::ANTERIOR_CARDIAC_VEINS => 'Anterior Cardiac Veins',
            self::OBLIQUE_VEIN_OF_LEFT_ATRIUM => 'Oblique Vein of Left Atrium (Vein of Marshall)',
            self::POSTERIOR_VEIN_OF_LEFT_VENTRICLE => 'Posterior Vein of Left Ventricle',
            self::ASCENDING_AORTA => 'Ascending Aorta',
            self::AORTIC_ARCH => 'Aortic Arch',
            self::DESCENDING_THORACIC_AORTA => 'Descending Thoracic Aorta',
            self::ABDOMINAL_AORTA => 'Abdominal Aorta',
            self::BRACHIOCEPHALIC_TRUNK => 'Brachiocephalic Trunk (Innominate Artery)',
            self::COMMON_CAROTID_ARTERY_LEFT => 'Common Carotid Artery (Left)',
            self::COMMON_CAROTID_ARTERY_RIGHT => 'Common Carotid Artery (Right)',
            self::SUBCLAVIAN_ARTERY_LEFT => 'Subclavian Artery (Left)',
            self::SUBCLAVIAN_ARTERY_RIGHT => 'Subclavian Artery (Right)',
            self::PULMONARY_TRUNK => 'Pulmonary Trunk',
            self::PULMONARY_ARTERY_LEFT => 'Left Pulmonary Artery',
            self::PULMONARY_ARTERY_RIGHT => 'Right Pulmonary Artery',
            self::PULMONARY_VEIN_LEFT_SUPERIOR => 'Left Superior Pulmonary Vein',
            self::PULMONARY_VEIN_LEFT_INFERIOR => 'Left Inferior Pulmonary Vein',
            self::PULMONARY_VEIN_RIGHT_SUPERIOR => 'Right Superior Pulmonary Vein',
            self::PULMONARY_VEIN_RIGHT_INFERIOR => 'Right Inferior Pulmonary Vein',
            self::SUPERIOR_VENA_CAVA => 'Superior Vena Cava — SVC',
            self::INFERIOR_VENA_CAVA => 'Inferior Vena Cava — IVC',
            self::AZYGOS_VEIN => 'Azygos Vein',
            self::HEMIAZYGOS_VEIN => 'Hemiazygos Vein',
            self::BRACHIOCEPHALIC_VEIN_LEFT => 'Left Brachiocephalic Vein',
            self::BRACHIOCEPHALIC_VEIN_RIGHT => 'Right Brachiocephalic Vein',
        };
    }

    public function category(): string
    {
        return match (true) {
            in_array($this, [
                self::INTERNAL_CAROTID_ARTERY_LEFT,
                self::INTERNAL_CAROTID_ARTERY_RIGHT,
                self::ANTERIOR_CEREBRAL_ARTERY_LEFT,
                self::ANTERIOR_CEREBRAL_ARTERY_RIGHT,
                self::MIDDLE_CEREBRAL_ARTERY_LEFT,
                self::MIDDLE_CEREBRAL_ARTERY_RIGHT,
                self::POSTERIOR_CEREBRAL_ARTERY_LEFT,
                self::POSTERIOR_CEREBRAL_ARTERY_RIGHT,
                self::ANTERIOR_COMMUNICATING_ARTERY,
                self::POSTERIOR_COMMUNICATING_ARTERY_LEFT,
                self::POSTERIOR_COMMUNICATING_ARTERY_RIGHT,
                self::BASILAR_ARTERY,
                self::VERTEBRAL_ARTERY_LEFT,
                self::VERTEBRAL_ARTERY_RIGHT,
                self::POSTERIOR_INFERIOR_CEREBELLAR_ARTERY_LEFT,
                self::POSTERIOR_INFERIOR_CEREBELLAR_ARTERY_RIGHT,
                self::ANTERIOR_INFERIOR_CEREBELLAR_ARTERY_LEFT,
                self::ANTERIOR_INFERIOR_CEREBELLAR_ARTERY_RIGHT,
                self::SUPERIOR_CEREBELLAR_ARTERY_LEFT,
                self::SUPERIOR_CEREBELLAR_ARTERY_RIGHT,
                self::OPHTHALMIC_ARTERY_LEFT,
                self::OPHTHALMIC_ARTERY_RIGHT,
                self::LENTICULOSTRIATE_ARTERY_LEFT,
                self::LENTICULOSTRIATE_ARTERY_RIGHT,
                self::PERICALLOSAL_ARTERY_LEFT,
                self::PERICALLOSAL_ARTERY_RIGHT,
                self::CALLOSOMARGINAL_ARTERY_LEFT,
                self::CALLOSOMARGINAL_ARTERY_RIGHT,
            ], true) => 'Cerebral Arteries',

            in_array($this, [
                self::SUPERIOR_SAGITTAL_SINUS,
                self::INFERIOR_SAGITTAL_SINUS,
                self::STRAIGHT_SINUS,
                self::TRANSVERSE_SINUS_LEFT,
                self::TRANSVERSE_SINUS_RIGHT,
                self::SIGMOID_SINUS_LEFT,
                self::SIGMOID_SINUS_RIGHT,
                self::CAVERNOUS_SINUS_LEFT,
                self::CAVERNOUS_SINUS_RIGHT,
                self::GREAT_CEREBRAL_VEIN_OF_GALEN,
                self::INTERNAL_CEREBRAL_VEIN_LEFT,
                self::INTERNAL_CEREBRAL_VEIN_RIGHT,
                self::BASAL_VEIN_OF_ROSENTHAL_LEFT,
                self::BASAL_VEIN_OF_ROSENTHAL_RIGHT,
                self::SUPERIOR_PETROSAL_SINUS_LEFT,
                self::SUPERIOR_PETROSAL_SINUS_RIGHT,
                self::INFERIOR_PETROSAL_SINUS_LEFT,
                self::INFERIOR_PETROSAL_SINUS_RIGHT,
                self::OCCIPITAL_SINUS,
                self::INTERNAL_JUGULAR_VEIN_LEFT,
                self::INTERNAL_JUGULAR_VEIN_RIGHT,
            ], true) => 'Cerebral Veins and Sinuses',

            in_array($this, [
                self::LEFT_MAIN_CORONARY_ARTERY,
                self::LEFT_ANTERIOR_DESCENDING_ARTERY,
                self::LEFT_CIRCUMFLEX_ARTERY,
                self::RIGHT_CORONARY_ARTERY,
                self::POSTERIOR_DESCENDING_ARTERY,
                self::OBTUSE_MARGINAL_ARTERY_FIRST,
                self::OBTUSE_MARGINAL_ARTERY_SECOND,
                self::DIAGONAL_BRANCH_FIRST,
                self::DIAGONAL_BRANCH_SECOND,
                self::SEPTAL_PERFORATOR_BRANCH,
                self::SINOATRIAL_NODE_ARTERY,
                self::ATRIOVENTRICULAR_NODE_ARTERY,
                self::ACUTE_MARGINAL_ARTERY,
                self::POSTEROLATERAL_BRANCH,
            ], true) => 'Coronary Arteries',

            in_array($this, [
                self::CORONARY_SINUS,
                self::GREAT_CARDIAC_VEIN,
                self::MIDDLE_CARDIAC_VEIN,
                self::SMALL_CARDIAC_VEIN,
                self::ANTERIOR_CARDIAC_VEINS,
                self::OBLIQUE_VEIN_OF_LEFT_ATRIUM,
                self::POSTERIOR_VEIN_OF_LEFT_VENTRICLE,
            ], true) => 'Cardiac Veins',

            in_array($this, [
                self::ASCENDING_AORTA,
                self::AORTIC_ARCH,
                self::DESCENDING_THORACIC_AORTA,
                self::ABDOMINAL_AORTA,
                self::BRACHIOCEPHALIC_TRUNK,
                self::COMMON_CAROTID_ARTERY_LEFT,
                self::COMMON_CAROTID_ARTERY_RIGHT,
                self::SUBCLAVIAN_ARTERY_LEFT,
                self::SUBCLAVIAN_ARTERY_RIGHT,
            ], true) => 'Aorta and Great Arteries',

            in_array($this, [
                self::PULMONARY_TRUNK,
                self::PULMONARY_ARTERY_LEFT,
                self::PULMONARY_ARTERY_RIGHT,
                self::PULMONARY_VEIN_LEFT_SUPERIOR,
                self::PULMONARY_VEIN_LEFT_INFERIOR,
                self::PULMONARY_VEIN_RIGHT_SUPERIOR,
                self::PULMONARY_VEIN_RIGHT_INFERIOR,
            ], true) => 'Pulmonary Vessels',

            default => 'Systemic Veins',
        };
    }

    public function isArtery(): bool
    {
        return in_array($this, [
            self::INTERNAL_CAROTID_ARTERY_LEFT,
            self::INTERNAL_CAROTID_ARTERY_RIGHT,
            self::ANTERIOR_CEREBRAL_ARTERY_LEFT,
            self::ANTERIOR_CEREBRAL_ARTERY_RIGHT,
            self::MIDDLE_CEREBRAL_ARTERY_LEFT,
            self::MIDDLE_CEREBRAL_ARTERY_RIGHT,
            self::POSTERIOR_CEREBRAL_ARTERY_LEFT,
            self::POSTERIOR_CEREBRAL_ARTERY_RIGHT,
            self::ANTERIOR_COMMUNICATING_ARTERY,
            self::POSTERIOR_COMMUNICATING_ARTERY_LEFT,
            self::POSTERIOR_COMMUNICATING_ARTERY_RIGHT,
            self::BASILAR_ARTERY,
            self::VERTEBRAL_ARTERY_LEFT,
            self::VERTEBRAL_ARTERY_RIGHT,
            self::POSTERIOR_INFERIOR_CEREBELLAR_ARTERY_LEFT,
            self::POSTERIOR_INFERIOR_CEREBELLAR_ARTERY_RIGHT,
            self::ANTERIOR_INFERIOR_CEREBELLAR_ARTERY_LEFT,
            self::ANTERIOR_INFERIOR_CEREBELLAR_ARTERY_RIGHT,
            self::SUPERIOR_CEREBELLAR_ARTERY_LEFT,
            self::SUPERIOR_CEREBELLAR_ARTERY_RIGHT,
            self::OPHTHALMIC_ARTERY_LEFT,
            self::OPHTHALMIC_ARTERY_RIGHT,
            self::LENTICULOSTRIATE_ARTERY_LEFT,
            self::LENTICULOSTRIATE_ARTERY_RIGHT,
            self::PERICALLOSAL_ARTERY_LEFT,
            self::PERICALLOSAL_ARTERY_RIGHT,
            self::CALLOSOMARGINAL_ARTERY_LEFT,
            self::CALLOSOMARGINAL_ARTERY_RIGHT,
            self::LEFT_MAIN_CORONARY_ARTERY,
            self::LEFT_ANTERIOR_DESCENDING_ARTERY,
            self::LEFT_CIRCUMFLEX_ARTERY,
            self::RIGHT_CORONARY_ARTERY,
            self::POSTERIOR_DESCENDING_ARTERY,
            self::OBTUSE_MARGINAL_ARTERY_FIRST,
            self::OBTUSE_MARGINAL_ARTERY_SECOND,
            self::DIAGONAL_BRANCH_FIRST,
            self::DIAGONAL_BRANCH_SECOND,
            self::SEPTAL_PERFORATOR_BRANCH,
            self::SINOATRIAL_NODE_ARTERY,
            self::ATRIOVENTRICULAR_NODE_ARTERY,
            self::ACUTE_MARGINAL_ARTERY,
            self::POSTEROLATERAL_BRANCH,
            self::ASCENDING_AORTA,
            self::AORTIC_ARCH,
            self::DESCENDING_THORACIC_AORTA,
            self::ABDOMINAL_AORTA,
            self::BRACHIOCEPHALIC_TRUNK,
            self::COMMON_CAROTID_ARTERY_LEFT,
            self::COMMON_CAROTID_ARTERY_RIGHT,
            self::SUBCLAVIAN_ARTERY_LEFT,
            self::SUBCLAVIAN_ARTERY_RIGHT,
            self::PULMONARY_TRUNK,
            self::PULMONARY_ARTERY_LEFT,
            self::PULMONARY_ARTERY_RIGHT,
        ], true);
    }

    public function isVein(): bool
    {
        return !$this->isArtery();
    }

    public function isCerebrovascular(): bool
    {
        return in_array($this->category(), [
            'Cerebral Arteries',
            'Cerebral Veins and Sinuses',
        ], true);
    }

    public function isCardiovascular(): bool
    {
        return in_array($this->category(), [
            'Coronary Arteries',
            'Cardiac Veins',
            'Aorta and Great Arteries',
            'Pulmonary Vessels',
            'Systemic Veins',
        ], true);
    }

    public function isCircleOfWillis(): bool
    {
        return in_array($this, [
            self::INTERNAL_CAROTID_ARTERY_LEFT,
            self::INTERNAL_CAROTID_ARTERY_RIGHT,
            self::ANTERIOR_CEREBRAL_ARTERY_LEFT,
            self::ANTERIOR_CEREBRAL_ARTERY_RIGHT,
            self::ANTERIOR_COMMUNICATING_ARTERY,
            self::MIDDLE_CEREBRAL_ARTERY_LEFT,
            self::MIDDLE_CEREBRAL_ARTERY_RIGHT,
            self::POSTERIOR_CEREBRAL_ARTERY_LEFT,
            self::POSTERIOR_CEREBRAL_ARTERY_RIGHT,
            self::POSTERIOR_COMMUNICATING_ARTERY_LEFT,
            self::POSTERIOR_COMMUNICATING_ARTERY_RIGHT,
            self::BASILAR_ARTERY,
        ], true);
    }

    public function isLeftDominant(): bool
    {
        return str_ends_with($this->value, '_left');
    }

    public function isRightDominant(): bool
    {
        return str_ends_with($this->value, '_right');
    }

    public function isBilateral(): bool
    {
        return !$this->isLeftDominant() && !$this->isRightDominant();
    }

    /** @return list<self> */
    public static function byCategory(string $category): array
    {
        return array_values(array_filter(self::cases(), fn(self $case) => $case->category() === $category));
    }

    /** @return list<self> */
    public static function arteries(): array
    {
        return array_values(array_filter(self::cases(), fn(self $case) => $case->isArtery()));
    }

    /** @return list<self> */
    public static function veins(): array
    {
        return array_values(array_filter(self::cases(), fn(self $case) => $case->isVein()));
    }

    /** @return list<self> */
    public static function cerebrovascular(): array
    {
        return array_values(array_filter(self::cases(), fn(self $case) => $case->isCerebrovascular()));
    }

    /** @return list<self> */
    public static function cardiovascular(): array
    {
        return array_values(array_filter(self::cases(), fn(self $case) => $case->isCardiovascular()));
    }

    /** @return list<self> */
    public static function circleOfWillis(): array
    {
        return array_values(array_filter(self::cases(), fn(self $case) => $case->isCircleOfWillis()));
    }
}
