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
 * The NeuronType enumeration provides a comprehensive classification of different types of neurons based on their functional roles, structural characteristics, neurotransmitter profiles, and anatomical locations. 
 * This enumeration can be used in various contexts, such as neuroscience research, clinical neurology, neuroanatomy education, and any application that requires a standardized way to refer to specific neuron types.
 */
enum NeuronType: string
{
    // -------------------------------------------------------------------------
    // Functional classification
    // -------------------------------------------------------------------------
    case UPPER_MOTOR_NEURON = 'upper_motor_neuron';
    case LOWER_MOTOR_NEURON = 'lower_motor_neuron';
    case SENSORY_NEURON = 'sensory_neuron';
    case INTERNEURON = 'interneuron';
    case ALPHA_MOTOR_NEURON = 'alpha_motor_neuron';
    case BETA_MOTOR_NEURON = 'beta_motor_neuron';
    case GAMMA_MOTOR_NEURON = 'gamma_motor_neuron';

    // -------------------------------------------------------------------------
    // Structural classification
    // -------------------------------------------------------------------------
    case UNIPOLAR = 'unipolar';
    case PSEUDOUNIPOLAR = 'pseudounipolar';
    case BIPOLAR = 'bipolar';
    case MULTIPOLAR = 'multipolar';
    case ANAXONIC = 'anaxonic';

    // -------------------------------------------------------------------------
    // Named neuron types
    // -------------------------------------------------------------------------
    case BETZ_CELL = 'betz_cell';
    case PURKINJE_CELL = 'purkinje_cell';
    case GRANULE_CELL = 'granule_cell';
    case BASKET_CELL = 'basket_cell';
    case STELLATE_CELL = 'stellate_cell';
    case RENSHAW_CELL = 'renshaw_cell';
    case PYRAMIDAL_CELL = 'pyramidal_cell';
    case CHANDELIER_CELL = 'chandelier_cell';
    case MIRROR_NEURON = 'mirror_neuron';
    case PLACE_CELL = 'place_cell';
    case GRID_CELL = 'grid_cell';

    // -------------------------------------------------------------------------
    // Neurotransmitter-defined
    // -------------------------------------------------------------------------
    case DOPAMINERGIC = 'dopaminergic';
    case SEROTONERGIC = 'serotonergic';
    case CHOLINERGIC = 'cholinergic';
    case GABAERGIC = 'gabaergic';
    case GLUTAMATERGIC = 'glutamatergic';
    case NORADRENERGIC = 'noradrenergic';
    case HISTAMINERGIC = 'histaminergic';
    case GLYCINERGIC = 'glycinergic';

    // -------------------------------------------------------------------------
    // Autonomic nervous system
    // -------------------------------------------------------------------------
    case PREGANGLIONIC_SYMPATHETIC = 'preganglionic_sympathetic';
    case POSTGANGLIONIC_SYMPATHETIC = 'postganglionic_sympathetic';
    case PREGANGLIONIC_PARASYMPATHETIC = 'preganglionic_parasympathetic';
    case POSTGANGLIONIC_PARASYMPATHETIC = 'postganglionic_parasympathetic';
    case ENTERIC_NEURON = 'enteric_neuron';

    // -------------------------------------------------------------------------
    // Cranial nerves (CN I–XII)
    // -------------------------------------------------------------------------
    case CN_I_OLFACTORY = 'cn_i_olfactory';
    case CN_II_OPTIC = 'cn_ii_optic';
    case CN_III_OCULOMOTOR = 'cn_iii_oculomotor';
    case CN_IV_TROCHLEAR = 'cn_iv_trochlear';
    case CN_V_TRIGEMINAL = 'cn_v_trigeminal';
    case CN_VI_ABDUCENS = 'cn_vi_abducens';
    case CN_VII_FACIAL = 'cn_vii_facial';
    case CN_VIII_VESTIBULOCOCHLEAR = 'cn_viii_vestibulocochlear';
    case CN_IX_GLOSSOPHARYNGEAL = 'cn_ix_glossopharyngeal';
    case CN_X_VAGUS = 'cn_x_vagus';
    case CN_XI_ACCESSORY = 'cn_xi_accessory';
    case CN_XII_HYPOGLOSSAL = 'cn_xii_hypoglossal';

    // -------------------------------------------------------------------------
    // Spinal nerves
    // -------------------------------------------------------------------------
    case SPINAL_CERVICAL = 'spinal_cervical';
    case SPINAL_THORACIC = 'spinal_thoracic';
    case SPINAL_LUMBAR = 'spinal_lumbar';
    case SPINAL_SACRAL = 'spinal_sacral';
    case SPINAL_COCCYGEAL = 'spinal_coccygeal';

    // -------------------------------------------------------------------------
    // Neural tracts & pathways
    // -------------------------------------------------------------------------
    case CORTICOSPINAL_TRACT = 'corticospinal_tract';
    case CORTICOBULBAR_TRACT = 'corticobulbar_tract';
    case SPINOTHALAMIC_TRACT = 'spinothalamic_tract';
    case DORSAL_COLUMN_MEDIAL_LEMNISCUS = 'dorsal_column_medial_lemniscus';
    case RUBROSPINAL_TRACT = 'rubrospinal_tract';
    case VESTIBULOSPINAL_TRACT = 'vestibulospinal_tract';
    case RETICULOSPINAL_TRACT = 'reticulospinal_tract';
    case TECTOSPINAL_TRACT = 'tectospinal_tract';
    case SPINOCEREBELLAR_TRACT = 'spinocerebellar_tract';

    // -------------------------------------------------------------------------
    // Sensory receptor neurons
    // -------------------------------------------------------------------------
    case NOCICEPTOR = 'nociceptor';
    case MECHANORECEPTOR = 'mechanoreceptor';
    case THERMORECEPTOR = 'thermoreceptor';
    case PHOTORECEPTOR = 'photoreceptor';
    case CHEMORECEPTOR = 'chemoreceptor';
    case PROPRIOCEPTOR = 'proprioceptor';
    case BARORECEPTOR = 'baroreceptor';

    // -------------------------------------------------------------------------
    // Glial cells
    // -------------------------------------------------------------------------
    case ASTROCYTE = 'astrocyte';
    case OLIGODENDROCYTE = 'oligodendrocyte';
    case SCHWANN_CELL = 'schwann_cell';
    case MICROGLIA = 'microglia';
    case EPENDYMAL_CELL = 'ependymal_cell';
    case SATELLITE_CELL = 'satellite_cell';

    /** 
     * This method provides a descriptive label for each neuron type, which can be used in clinical documentation, educational materials, or any context where a clear explanation of the neuron type is needed.
     */
    public function label(): string
    {
        return match ($this) {
            self::UPPER_MOTOR_NEURON => 'Upper Motor Neuron (UMN)',
            self::LOWER_MOTOR_NEURON => 'Lower Motor Neuron (LMN)',
            self::SENSORY_NEURON => 'Sensory Neuron (Afferent)',
            self::INTERNEURON => 'Interneuron (Association Neuron)',
            self::ALPHA_MOTOR_NEURON => 'Alpha (α) Motor Neuron',
            self::BETA_MOTOR_NEURON => 'Beta (β) Motor Neuron',
            self::GAMMA_MOTOR_NEURON => 'Gamma (γ) Motor Neuron',
            self::UNIPOLAR => 'Unipolar Neuron',
            self::PSEUDOUNIPOLAR => 'Pseudounipolar Neuron',
            self::BIPOLAR => 'Bipolar Neuron',
            self::MULTIPOLAR => 'Multipolar Neuron',
            self::ANAXONIC => 'Anaxonic Neuron',
            self::BETZ_CELL => 'Betz Cell (Giant Pyramidal Motor Neuron)',
            self::PURKINJE_CELL => 'Purkinje Cell',
            self::GRANULE_CELL => 'Granule Cell',
            self::BASKET_CELL => 'Basket Cell',
            self::STELLATE_CELL => 'Stellate Cell (Star Cell)',
            self::RENSHAW_CELL => 'Renshaw Cell',
            self::PYRAMIDAL_CELL => 'Pyramidal Cell',
            self::CHANDELIER_CELL => 'Chandelier Cell (Axo-axonic Cell)',
            self::MIRROR_NEURON => 'Mirror Neuron',
            self::PLACE_CELL => 'Place Cell',
            self::GRID_CELL => 'Grid Cell',
            self::DOPAMINERGIC => 'Dopaminergic Neuron',
            self::SEROTONERGIC => 'Serotonergic Neuron',
            self::CHOLINERGIC => 'Cholinergic Neuron',
            self::GABAERGIC => 'GABAergic Neuron',
            self::GLUTAMATERGIC => 'Glutamatergic Neuron',
            self::NORADRENERGIC => 'Noradrenergic Neuron',
            self::HISTAMINERGIC => 'Histaminergic Neuron',
            self::GLYCINERGIC => 'Glycinergic Neuron',
            self::PREGANGLIONIC_SYMPATHETIC => 'Preganglionic Sympathetic Neuron',
            self::POSTGANGLIONIC_SYMPATHETIC => 'Postganglionic Sympathetic Neuron',
            self::PREGANGLIONIC_PARASYMPATHETIC => 'Preganglionic Parasympathetic Neuron',
            self::POSTGANGLIONIC_PARASYMPATHETIC => 'Postganglionic Parasympathetic Neuron',
            self::ENTERIC_NEURON => 'Enteric Neuron',
            self::CN_I_OLFACTORY => 'CN I — Olfactory Nerve',
            self::CN_II_OPTIC => 'CN II — Optic Nerve',
            self::CN_III_OCULOMOTOR => 'CN III — Oculomotor Nerve',
            self::CN_IV_TROCHLEAR => 'CN IV — Trochlear Nerve',
            self::CN_V_TRIGEMINAL => 'CN V — Trigeminal Nerve',
            self::CN_VI_ABDUCENS => 'CN VI — Abducens Nerve',
            self::CN_VII_FACIAL => 'CN VII — Facial Nerve',
            self::CN_VIII_VESTIBULOCOCHLEAR => 'CN VIII — Vestibulocochlear Nerve',
            self::CN_IX_GLOSSOPHARYNGEAL => 'CN IX — Glossopharyngeal Nerve',
            self::CN_X_VAGUS => 'CN X — Vagus Nerve',
            self::CN_XI_ACCESSORY => 'CN XI — Accessory Nerve',
            self::CN_XII_HYPOGLOSSAL => 'CN XII — Hypoglossal Nerve',
            self::SPINAL_CERVICAL => 'Cervical Spinal Nerves (C1–C8)',
            self::SPINAL_THORACIC => 'Thoracic Spinal Nerves (T1–T12)',
            self::SPINAL_LUMBAR => 'Lumbar Spinal Nerves (L1–L5)',
            self::SPINAL_SACRAL => 'Sacral Spinal Nerves (S1–S5)',
            self::SPINAL_COCCYGEAL => 'Coccygeal Spinal Nerve (Co1)',
            self::CORTICOSPINAL_TRACT => 'Corticospinal Tract (Pyramidal Tract)',
            self::CORTICOBULBAR_TRACT => 'Corticobulbar Tract',
            self::SPINOTHALAMIC_TRACT => 'Spinothalamic Tract',
            self::DORSAL_COLUMN_MEDIAL_LEMNISCUS => 'Dorsal Column–Medial Lemniscal Pathway',
            self::RUBROSPINAL_TRACT => 'Rubrospinal Tract',
            self::VESTIBULOSPINAL_TRACT => 'Vestibulospinal Tract',
            self::RETICULOSPINAL_TRACT => 'Reticulospinal Tract',
            self::TECTOSPINAL_TRACT => 'Tectospinal Tract',
            self::SPINOCEREBELLAR_TRACT => 'Spinocerebellar Tract',
            self::NOCICEPTOR => 'Nociceptor (Pain Receptor)',
            self::MECHANORECEPTOR => 'Mechanoreceptor',
            self::THERMORECEPTOR => 'Thermoreceptor',
            self::PHOTORECEPTOR => 'Photoreceptor',
            self::CHEMORECEPTOR => 'Chemoreceptor',
            self::PROPRIOCEPTOR => 'Proprioceptor',
            self::BARORECEPTOR => 'Baroreceptor',
            self::ASTROCYTE => 'Astrocyte',
            self::OLIGODENDROCYTE => 'Oligodendrocyte',
            self::SCHWANN_CELL => 'Schwann Cell',
            self::MICROGLIA => 'Microglia',
            self::EPENDYMAL_CELL => 'Ependymal Cell',
            self::SATELLITE_CELL => 'Satellite Cell',
        };
    }

    /** 
     * Motor neurons are responsible for transmitting motor commands from the central nervous system (CNS) to the muscles, and they can be classified into various types based on their specific functions, anatomical locations, and neurotransmitter profiles. 
     * Motor neurons include upper motor neurons (UMNs) that originate in the brain and descend to the spinal cord, lower motor neurons (LMNs) that originate in the spinal cord and innervate muscles, alpha motor neurons that innervate extrafusal muscle fibers, beta motor neurons that innervate both extrafusal and intrafusal muscle fibers, gamma motor neurons that innervate intrafusal muscle fibers, Betz cells in the primary motor cortex, and certain descending tracts such as the corticospinal and corticobulbar tracts.
     */
    public function isMotor(): bool
    {
        return in_array($this, [
            self::UPPER_MOTOR_NEURON,
            self::LOWER_MOTOR_NEURON,
            self::ALPHA_MOTOR_NEURON,
            self::BETA_MOTOR_NEURON,
            self::GAMMA_MOTOR_NEURON,
            self::BETZ_CELL,
            self::CORTICOSPINAL_TRACT,
            self::CORTICOBULBAR_TRACT,
        ]);
    }

    /** 
     * Sensory neurons are responsible for transmitting sensory information from the peripheral sensory receptors to the central nervous system (CNS), and they can be classified into various types based on their specific functions, anatomical locations, and neurotransmitter profiles. 
     * Sensory neurons include specialized receptor neurons such as nociceptors, mechanoreceptors, thermoreceptors, photoreceptors, chemoreceptors, proprioceptors, and baroreceptors, as well as cranial nerve sensory neurons and spinal nerve sensory neurons that transmit information from the body to the CNS.
     */
    public function isSensory(): bool
    {
        return in_array($this, [
            self::SENSORY_NEURON,
            self::NOCICEPTOR,
            self::MECHANORECEPTOR,
            self::THERMORECEPTOR,
            self::PHOTORECEPTOR,
            self::CHEMORECEPTOR,
            self::PROPRIOCEPTOR,
            self::BARORECEPTOR,
            self::CN_I_OLFACTORY,
            self::CN_II_OPTIC,
            self::CN_VIII_VESTIBULOCOCHLEAR,
            self::SPINOTHALAMIC_TRACT,
            self::DORSAL_COLUMN_MEDIAL_LEMNISCUS,
        ]);
    }

    /** 
     * Interneurons are a diverse group of neurons that serve as connectors and modulators within neural circuits. 
     * They can be classified into various types based on their specific functions, anatomical locations, neurotransmitter profiles, and morphological characteristics. 
     * Interneurons play crucial roles in processing and integrating information, regulating the flow of signals between sensory and motor neurons, and facilitating complex behaviors such as learning, memory, and cognition.
     */
    public function isInterneuron(): bool
    {
        return in_array($this, [
            self::INTERNEURON,
            self::RENSHAW_CELL,
            self::PURKINJE_CELL,
            self::GRANULE_CELL,
            self::BASKET_CELL,
            self::STELLATE_CELL,
            self::CHANDELIER_CELL,
            self::PYRAMIDAL_CELL,
        ]);
    }

    /** 
     * Upper motor neurons (UMNs) are responsible for transmitting motor commands from the brain to the spinal cord, and they can be classified into various types based on their specific functions, anatomical locations, and neurotransmitter profiles. 
     * UMNs are located in the cerebral cortex (such as Betz cells in the primary motor cortex), brainstem nuclei, and certain descending tracts (such as the corticospinal and corticobulbar tracts), and they play a crucial role in initiating and modulating voluntary movements.
     */
    public function isUpperMotorNeuron(): bool
    {
        return in_array($this, [
            self::UPPER_MOTOR_NEURON,
            self::BETZ_CELL,
            self::CORTICOSPINAL_TRACT,
            self::CORTICOBULBAR_TRACT,
        ]);
    }

    /** 
     * Lower motor neurons (LMNs) are responsible for transmitting motor commands from the central nervous system (CNS) to the muscles, and they can be classified into alpha, beta, and gamma motor neurons based on their specific functions and target muscle fibers. 
     * LMNs are located in the anterior horn of the spinal cord and the cranial nerve nuclei in the brainstem, and they directly innervate skeletal muscles to produce voluntary movements.
     */
    public function isLowerMotorNeuron(): bool
    {
        return in_array($this, [
            self::LOWER_MOTOR_NEURON,
            self::ALPHA_MOTOR_NEURON,
            self::BETA_MOTOR_NEURON,
            self::GAMMA_MOTOR_NEURON,
        ]);
    }

    /** 
     * Autonomic neurons are part of the autonomic nervous system (ANS), which regulates involuntary physiological functions such as heart rate, digestion, respiratory rate, pupillary response, urination, and sexual arousal. 
     * These neurons can be classified into sympathetic and parasympathetic types based on their anatomical location, neurotransmitter profile, and functional role in regulating specific target organs and tissues throughout the body.
     */
    public function isAutonomic(): bool
    {
        return in_array($this, [
            self::PREGANGLIONIC_SYMPATHETIC,
            self::POSTGANGLIONIC_SYMPATHETIC,
            self::PREGANGLIONIC_PARASYMPATHETIC,
            self::POSTGANGLIONIC_PARASYMPATHETIC,
            self::ENTERIC_NEURON,
            self::CN_X_VAGUS,
        ]);
    }

    /** 
     * Cranial nerves are a set of twelve paired nerves that emerge directly from the brain and brainstem, rather than from the spinal cord. 
     * They are responsible for a wide range of sensory and motor functions in the head and neck, including vision, hearing, taste, smell, facial movement, and autonomic regulation. 
     * The classification of a neuron as a cranial nerve is based on its anatomical origin (emerging from the brain or brainstem) and its specific functional role in innervating structures in the head and neck region.
     */
    public function isCranialNerve(): bool
    {
        return in_array($this, [
            self::CN_I_OLFACTORY,
            self::CN_II_OPTIC,
            self::CN_III_OCULOMOTOR,
            self::CN_IV_TROCHLEAR,
            self::CN_V_TRIGEMINAL,
            self::CN_VI_ABDUCENS,
            self::CN_VII_FACIAL,
            self::CN_VIII_VESTIBULOCOCHLEAR,
            self::CN_IX_GLOSSOPHARYNGEAL,
            self::CN_X_VAGUS,
            self::CN_XI_ACCESSORY,
            self::CN_XII_HYPOGLOSSAL,
        ]);
    }

    /** 
     * Spinal nerves are part of the peripheral nervous system (PNS) and consist of mixed nerves that contain both sensory and motor fibers. 
     * They are responsible for transmitting sensory information from the body to the central nervous system (CNS) and conveying motor commands from the CNS to muscles and glands. 
     * The classification of a neuron as a spinal nerve is based on its anatomical location, as spinal nerves emerge from the spinal cord and are organized into cervical, thoracic, lumbar, sacral, and coccygeal regions.
     */
    public function isSpinalNerve(): bool
    {
        return in_array($this, [
            self::SPINAL_CERVICAL,
            self::SPINAL_THORACIC,
            self::SPINAL_LUMBAR,
            self::SPINAL_SACRAL,
            self::SPINAL_COCCYGEAL,
        ]);
    }

    /** 
     * Neural tracts are bundles of axons that connect different regions of the central nervous system (CNS) and facilitate communication between them. 
     * These tracts can be classified based on their direction (ascending or descending), function (motor or sensory), and anatomical location. 
     * The classification of a neuron as part of a tract is based on its role in transmitting information along a specific pathway within the CNS.
     */
    public function isTract(): bool
    {
        return in_array($this, [
            self::CORTICOSPINAL_TRACT,
            self::CORTICOBULBAR_TRACT,
            self::SPINOTHALAMIC_TRACT,
            self::DORSAL_COLUMN_MEDIAL_LEMNISCUS,
            self::RUBROSPINAL_TRACT,
            self::VESTIBULOSPINAL_TRACT,
            self::RETICULOSPINAL_TRACT,
            self::TECTOSPINAL_TRACT,
            self::SPINOCEREBELLAR_TRACT,
        ]);
    }

    /** 
     * Glial cells are non-neuronal cells in the nervous system that provide support, protection, and nourishment to neurons. 
     * They play crucial roles in maintaining homeostasis, forming myelin, providing structural support, and participating in immune responses within the nervous system. 
     * The classification of a cell as glial is based on its functional role and characteristics rather than its neurotransmitter profile or anatomical location.
     */
    public function isGlial(): bool
    {
        return in_array($this, [
            self::ASTROCYTE,
            self::OLIGODENDROCYTE,
            self::SCHWANN_CELL,
            self::MICROGLIA,
            self::EPENDYMAL_CELL,
            self::SATELLITE_CELL,
        ]);
    }

    /** 
     * Excitatory neurons are those that release neurotransmitters that increase the likelihood of an action potential being generated in the postsynaptic neuron. 
     * These neurons play a crucial role in facilitating communication between neurons, promoting synaptic plasticity, and supporting various cognitive and motor functions. 
     * Excitatory neurons typically release neurotransmitters such as glutamate, acetylcholine, dopamine, norepinephrine, histamine, or serotonin, which bind to receptors on the postsynaptic neuron and cause depolarization, making it more likely for the neuron to fire an action potential.
     */
    public function isExcitatory(): bool
    {
        return in_array($this, [
            self::GLUTAMATERGIC,
            self::CHOLINERGIC,
            self::DOPAMINERGIC,
            self::NORADRENERGIC,
            self::HISTAMINERGIC,
            self::SEROTONERGIC,
        ]);
    }

    /** 
     * Inhibitory neurons are those that release neurotransmitters that decrease the likelihood of an action potential being generated in the postsynaptic neuron. 
     * These neurons play a crucial role in regulating neural circuits, maintaining the balance between excitation and inhibition, and preventing excessive neuronal activity that can lead to conditions such as epilepsy. 
     * Inhibitory neurons typically release neurotransmitters such as gamma-aminobutyric acid (GABA) or glycine, which bind to receptors on the postsynaptic neuron and cause hyperpolarization, making it less likely for the neuron to fire an action potential.
     */
    public function isInhibitory(): bool
    {
        return in_array($this, [
            self::GABAERGIC,
            self::GLYCINERGIC,
            self::RENSHAW_CELL,
            self::PURKINJE_CELL,
            self::BASKET_CELL,
            self::CHANDELIER_CELL,
        ]);
    }

    /** 
     * Central neurons are those that are located within the central nervous system (CNS), which includes the brain and spinal cord. 
     * These neurons are responsible for processing and integrating information, coordinating complex behaviors, and facilitating communication between different regions of the CNS. 
     * Central neurons can be further classified based on their specific functions, such as motor control, sensory processing, cognitive functions, and autonomic regulation.
     */
    public function isCentral(): bool
    {
        return !$this->isPeripheral();
    }

    /** 
     * Peripheral neurons include cranial nerves, spinal nerves, autonomic neurons, and certain specialized neuron types that are located outside the central nervous system (CNS). 
     * These neurons are responsible for transmitting sensory information from the periphery to the CNS, conveying motor commands from the CNS to muscles and glands, and regulating autonomic functions throughout the body. 
     * The classification of a neuron as peripheral is based on its anatomical location and functional role in the nervous system.
     */
    public function isPeripheral(): bool
    {
        return $this->isCranialNerve()
            || $this->isSpinalNerve()
            || in_array($this, [
                self::POSTGANGLIONIC_SYMPATHETIC,
                self::POSTGANGLIONIC_PARASYMPATHETIC,
                self::ENTERIC_NEURON,
                self::SCHWANN_CELL,
                self::SATELLITE_CELL,
                self::PSEUDOUNIPOLAR,
            ]);
    }
}
