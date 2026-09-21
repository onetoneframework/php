<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Enumeration\Medical;

abstract class SurgeryType
{
    public const THYROID_CARTILAGE_AND_VOCAL_FOLD_REDUCTION = 'Thyroid Cartilage and Vocal Fold Reduction';
    /**
     * Tracheal Shave, Often performed alongside or in conjunction with voice procedures, this involves reducing the size of the laryngeal prominence (Adam’s apple) for a smoother neck contour.
     * @var string
     */
    public const CHONDROLARYNGOPLASTY = 'Chondrolaryngoplasty';
    public const VOICE_FEMINIZATION_SURGERY = 'Voice Feminization Surgery';
    public const VOICE_MASCULINIZATION_SURGERY = 'Voice Masculinization Surgery';
    /**
     * It is performed through the mouth (endoscopically) with no external incisions. The surgeon strips the lining from the front third of the vocal folds and sutures them together.
     * This effectively shortens the vibrating part of the vocal cords. Shorter cords vibrate faster, resulting in a higher-pitched voice.
     * @var string
     */
    public const WENDLER_GLOTTOPLASTY = 'Wendler Glottoplasty';
    public const GLOTTOPLASTY = 'Glottoplasty';
    /**
     * An incision is made in the neck. The surgeon uses stitches or metal clips to pull the cricoid cartilage and thyroid cartilage closer together.
     * This mimics the action of the cricothyroid muscle, placing the vocal cords under constant tension (similar to stretching a guitar string), which raises the pitch.
     * @var string
     */
    public const CRICOTHYROID_APPROXIMATION = 'Cricothyroid Approximation';
    public const FACIAL_FEMINIZATION_SURGERY = 'Facial Feminization Surgery';
    /**
     * The construction of a vagina using penile and scrotal tissue (penile inversion) or, in some cases, intestinal tissue.
     * @var string
     */
    public const VAGINOPLASTY = 'Vaginoplasty';
    /**
     * The surgical removal of the testicles.
     * @var string
     */
    public const ORCHIECTOMY = 'Orchiectomy';
    /**
     * The creation of the labia majora and minora.
     * @var string
     */
    public const LABIAPLASTY = 'Labiaplasty';
    /**
     * The creation of a clitoris from the glans of the penis.
     * @var string
     */
    public const CLITOROPLASTY = 'Clitoroplasty';
    /**
     * Breast Augmentation, The surgical placement of breast implants to increase breast size.
     * @var string
     */
    public const MAMMAPLASTY = 'Mammaplasty';
    /**
     * The construction of a phallus (penis) using skin grafts, usually taken from the forearm, thigh, or back.
     * @var string
     */
    public const PHALLOPLASTY = 'Phalloplasty';
    /**
     * A procedure that releases the clitoris (which has enlarged due to testosterone therapy) to function as a small penis.
     * @var string
     */
    public const METOIDIOPLASTY = 'Metoidioplasty';
    /**
     * The construction of a scrotum, often using the labia majora.
     * @var string
     */
    public const SCROTOPLASTY = 'Scrotoplasty';
    /**
     * Extending the urethra so that the person can urinate through the newly constructed phallus or metoidioplasty.
     * @var string
     */
    public const URETHRAL_LENGTHENING = 'Urethral Lengthening';
    /**
     * Removal of the uterus.
     * @var string
     */
    public const HYSTERECTOMY = 'Hysterectomy';
    /**
     * Removal of the ovaries.
     * @var string
     */
    public const OOPHORECTOMY = 'Oophorectomy';
    /**
     * Closing the vaginal canal.
     * @var string
     */
    public const VAGINECTOMY = 'Vaginectomy';
    /**
     * The most common type of brain surgery. A bone flap is removed from the skull to access the brain, then replaced after the procedure is finished.
     * @var string
     */
    public const CRANIOTOMY = 'Craniotomy';
    /**
     * Similar to a craniotomy, but the bone flap is not replaced immediately (often done to allow the brain to swell after severe trauma).
     * @var string
     */
    public const CRANIECTOMY = 'Craniectomy';
    /**
     * A small hole drilled into the skull to relieve pressure (e.g., from a subdural hematoma) or to place a drain.
     * @var string
     */
    public const BURR_HOLE = 'Burr Hole';
    /**
     * A minimally invasive technique where surgeons operate through the nose and sinuses to reach the base of the brain or the pituitary gland.
     * @var string
     */
    public const ENDOSCOPIC_ENDONASAL_SURGERY = 'Endoscopic Endonasal Surgery';
    /**
     * (e.g., Gamma Knife): Not "surgery" in the traditional sense, but a non-invasive procedure that uses high-dose radiation to shrink or destroy a tumor.
     * @var string
     */
    public const STEREOTACTIC_RADIOSURGERY = 'Stereotactic Radiosurgery';
    /**
     * Taking a small sample of brain tissue to determine if a mass is cancerous or benign.
     * @var string
     */
    public const BIOPSY = 'Biopsy';
    /**
     * (Endovascular) A minimally invasive procedure where a catheter is threaded through an artery to fill the aneurysm with tiny platinum coils.
     * @var string
     */
    public const ANEURYSM_CLIPPING = 'Aneurysm Clipping';
    /**
     * The surgical removal or disconnection of a tangled web of abnormal blood vessels.
     * @var string
     */
    public const ARTERIOVENOUS_MALFORMATION = 'Arteriovenous Malformation';
    /**
     * Removing plaque buildup from the carotid arteries in the neck to restore blood flow to the brain and prevent stroke.
     * @var string
     */
    public const CAROTID_ENDARTERECTOMY = 'Carotid Endarterectomy';
    /**
     * Implantation of electrodes in specific parts of the brain to treat movement disorders like Parkinson’s disease, tremors, or dystonia.
     * @var string
     */
    public const DEEP_BRAIN_STIMULATION = 'Deep Brain Stimulation';
    /**
     * Cutting the connection between the two halves of the brain to stop the spread of severe seizures.
     * @var string
     */
    public const CORPUS_CALLOSOTOMY = 'Corpus Callosotomy';
    /**
     * Removing a specific, small area of the brain that is causing seizures.
     * @var string
     */
    public const LESIONECTOMY = 'Lesionectomy';
    /**
     * Implantation of a device that sends regular, mild pulses of electrical energy to the brain via the vagus nerve to reduce seizures.
     * @var string
     */
    public const VAGUS_NERVE_STIMULATION = 'Vagus Nerve Stimulation';
    /**
     * Placement of a catheter to drain excess cerebrospinal fluid (CSF) from the brain into the abdomen.
     * @var string
     */
    public const VENTRICULOPERITONEAL_SHUNT = 'Ventriculoperitoneal Shunt';
    /**
     * Removing a damaged disc to relieve spinal cord or nerve root pressure.
     * @var string
     */
    public const ANTERIOR_CERVICAL_DISCECTOMY_AND_FUSION = 'Anterior Cervical Discectomy and Fusion';
    /**
     * Removing or reshaping the back part of the vertebrae to create more room for the spinal cord (usually for spinal stenosis).
     * @var string
     */
    public const CERVICAL_LAMINECTOMY = 'Cervical Laminectomy';
    /**
     * Replacing a damaged disc with an artificial one to maintain motion.
     * @var string
     */
    public const Cervical_Disc_Replacement = 'Cervical Disc Replacement';
    /**
     * Removal of all or part of the thyroid gland (often for nodules, cancer, or hyperthyroidism).
     * @var string
     */
    public const THYROIDECTOMY = 'Thyroidectomy';
    /**
     * Removal of one or more of the parathyroid glands (which control calcium levels).
     * @var string
     */
    public const PARATHYROIDECTOMY = 'Parathyroidectomy';
    /**
     * Removing a common type of congenital neck cyst.
     * @var string
     */
    public const THYROGLOSSAL_DUCT_CYST_REMOVAL = 'Thyroglossal Duct Cyst Removal';
    /**
     * The surgeon brings the thyroid cartilage closer to the hyoid bone (higher up in the neck).
     * This shortens the vocal tract, which shifts the resonance of the voice to sound more feminine (less "boomy").
     * @var string
     */
    public const THYROHYOID_APPROXIMATION = 'Thyrohyoid Approximation';
    /**
     * By reducing the mass of the vocal folds (thinning them out), they vibrate faster, increasing the pitch.
     * Results can be unpredictable, and there is a risk of permanent hoarseness if too much tissue is scarred.
     * @var string
     */
    public const LASER_ASSISTED_VOICE_ADJUSTMENT = 'Laser-Assisted Voice Adjustment';
    /**
     * Through an incision in the neck, the surgeon removes a vertical strip of the thyroid cartilage and narrows the diameter of the larynx. It often includes a "thyrohyoid approximation" to move the larynx higher in the neck.
     * It addresses both **pitch** (by shortening the cords) and **resonance** (by shrinking the "echo chamber" of the throat).
     * @var string
     */
    public const FEMINIZATION_LARYNGOPLASTY = 'Feminization Laryngoplasty';

}
