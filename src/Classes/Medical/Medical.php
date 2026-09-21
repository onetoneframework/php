<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\Medical;

use Exception;

/**
 * Class Medical
 * 
 * A class to analyze various medical test results and provide risk assessments.
 */
class Medical
{
    /** 
     * Gender of the patient, expected values
     * @var string|null $gender 
     **/
    private ?string $gender = null;
    /** 
     * Age of the patient in years
     * @var float|null $age 
     **/
    private ?float $age = null;
    /** 
     * Fasting Blood Sugar value in mg/dL
     * @var int|null $fsbValue 
     **/
    private ?int $fsbValue = null;
    /** 
     * Low Density Lipoprotein value in mg/dL
     * @var int|null $ldlValue 
     **/
    private ?int $ldlValue = null;
    /** 
     * High Density Lipoprotein value in mg/dL
     * @var int|null $hdlValue 
     **/
    private ?int $hdlValue = null;
    /** 
     * Estimated Glomerular Filtration Rate value in mL/min/1.73m²
     * @var int|null $eGprValue 
     **/
    private ?int $eGprValue = null;
    /** 
     * Aspartate Aminotransferase value in U/L
     * @var int|null $astValue 
     **/
    private ?int $astValue = null;
    /** 
     * Alanine Aminotransferase value in U/L
     * @var int|null $altValue 
     **/
    private ?int $altValue = null;
    /** 
     * Gamma Glutamyl Transferase value in U/L
     * @var int|null $gammaGGTValue 
     **/
    private ?int $gammaGTPValue = null;
    /** 
     * Gamma-Glutamyltransferase value in U/L
     * @var int|null $gammaGGTValue 
     **/
    private ?int $gammaGGTValue = null;
    /** 
     * Neutral Fat value in mg/dL
     * @var int|null $neutralFatValue 
     **/
    private ?int $neutralFatValue = null;
    /** 
     * Total Cholesterol value in mg/dL
     * @var int|null $totalCholesterolValue 
     **/
    private ?int $totalCholesterolValue = null;
    /** 
     * Serum Creatinine value in mg/dL
     * @var float|null $serumCreatinineValue 
     **/
    private ?float $serumCreatinineValue = null;
    /** 
     * Hemoglobin value in g/dL
     * @var float|null $hemoglobinValue 
     **/
    private ?float $hemoglobinValue = null;
    /** 
     * Systolic Blood Pressure value in mmHg
     * @var int|null $systolicBloodPressureValue 
     **/
    private ?int $systolicBloodPressureValue = null;
    /** 
     * Diastolic Blood Pressure value in mmHg
     * @var int|null $diastolicBloodPressureValue 
     **/
    private ?int $diastolicBloodPressureValue = null;
    /** 
     * HbA1c value in %
     * @var float|null $hba1cValue 
     **/
    private ?float $hba1cValue = null;
    /** 
     * Height value in cm
     * @var float|null $height 
     **/
    private ?float $height = null;
    /** 
     * Weight value in kg
     * @var float|null $weight 
     **/
    private ?float $weight = null;
    /** 
     * Waist Circumference value in cm
     * @var float|null $waistCircumference 
     **/
    private ?float $waistCircumference = null;
    /** 
     * Heart Rate value in bpm
     * @var int|null $heartRate 
     **/
    private ?int $heartRate = null;
    /** 
     * Uric Acid value in mg/dL
     * @var float|null $uricAcidValue 
     **/
    private ?float $uricAcidValue = null;

    /**
     * Set diastolic blood pressure value
     * 
     * @param int $value
     * 
     * @return void
     */
    public function setDiastolicBloodPressureValue(int $value): static
    {
        $this->diastolicBloodPressureValue = $value;
        return $this;
    }

    /**
     * Set systolic blood pressure value
     * 
     * @param int $value
     * 
     * @return void: void
     */
    public function setSystolicBloodPressureValue(int $value): static
    {
        $this->systolicBloodPressureValue = $value;
        return $this;
    }

    /**
     * Set age value
     * 
     * @param float $value
     * 
     * @return void
     */
    public function setAge(float $value): static
    {
        $this->age = $value;
        return $this;
    }

    /**
     * Set gender value
     * 
     * @param string $value
     * 
     * @return void
     */
    public function setGender(string $value): static
    {
        $this->gender = strtolower($value);
        return $this;
    }

    /**
     * Set hemoglobin value
     * 
     * @param float $value
     * 
     * @return void
     */
    public function setHemoglobinValue(float $value): static
    {
        $this->hemoglobinValue = $value;
        return $this;
    }

    /**
     * Set serum creatinine value
     * 
     * @param float $value
     * 
     * @return void
     */
    public function setSerumCreatinineValue(float $value): static
    {
        $this->serumCreatinineValue = $value;
        return $this;
    }

    /**
     * Set fasting blood sugar value
     * 
     * @param int $value
     * 
     * @return void
     */
    public function setFBSValue(int $value): static
    {
        $this->fsbValue = $value;
        return $this;
    }

    /**
     * Set aspartate aminotransferase value
     * 
     * @param int $value
     * 
     * @return void
     */
    public function setASTValue(int $value): static
    {
        $this->astValue = $value;
        return $this;
    }

    /**
     * Set alanine aminotransferase value
     * 
     * @param int $value
     * 
     * @return void
     */
    public function setALTValue(int $value): static
    {
        $this->altValue = $value;
        return $this;
    }

    /**
     * Set estimated glomerular filtration rate value
     * 
     * @param int $value
     * 
     * @return void
     */
    public function setEGprValue(int $value): static
    {
        $this->eGprValue = $value;
        return $this;
    }

    /**
     * Set neutral fat rate value
     * 
     * @param int $value
     * 
     * @return void
     */
    public function setNeutralFatValue(int $value): static
    {
        $this->neutralFatValue = $value;
        return $this;
    }

    /**
     * Set total cholesterol value
     * 
     * @param int $value
     * 
     * @return void
     */
    public function setTotalCholesterolValue(int $value): static
    {
        $this->totalCholesterolValue = $value;
        return $this;
    }

    /**
     * Set gamma gtp value
     * 
     * @param int $value
     * 
     * @return void
     */
    public function setGammaGTPValue(int $value): static
    {
        $this->gammaGTPValue = $value;
        return $this;
    }

    /**
     * Set gamma-glutamyltransferase value
     * 
     * @param int $value
     * 
     * @return void
     */
    public function setGammaGGTValue(int $value): static
    {
        $this->gammaGGTValue = $value;
        return $this;
    }

    /**
     * Low Density Lipoprotein
     * 
     * @param int $value
     * 
     * @return void
     */
    public function setLdlValue(int $value): static
    {
        $this->ldlValue = $value;
        return $this;
    }

    /**
     * Set High Density Lipoprotein value
     * 
     * @param int $value
     * 
     * @return void
     */
    public function setHdlValue(int $value): static
    {
        $this->hdlValue = $value;
        return $this;
    }

    /**
     * Set HbA1c value
     * 
     * @param float $value
     * 
     * @return void
     */
    public function setHba1cValue(float $value): static
    {
        $this->hba1cValue = $value;
        return $this;
    }

    /**
     * Set height value
     * 
     * @param float $value
     * 
     * @return void
     */
    public function setHeight(float $value): static
    {
        $this->height = $value;
        return $this;
    }

    /**
     * Set weight value
     * 
     * @param float $value
     * 
     * @return void
     */
    public function setWeight(float $value): static
    {
        $this->weight = $value;
        return $this;
    }

    /**
     * Set waist circumference value
     * 
     * @param float $value
     * 
     * @return void
     */
    public function setWaistCircumference(float $value): static
    {
        $this->waistCircumference = $value;
        return $this;
    }

    /**
     * Set heart rate value
     * 
     * @param int $value
     * 
     * @return void
     */
    public function setHeartRate(int $value): static
    {
        $this->heartRate = $value;
        return $this;
    }

    /**
     * Set uric acid value
     * 
     * @param float $value
     * 
     * @return void
     */
    public function setUricAcidValue(float $value): static
    {
        $this->uricAcidValue = $value;
        return $this;
    }

    /**
     * Get hemoglobin risk based on age
     * 
     * @return ?string
     * 
     * @throws Exception: string
     */
    public function getHemoglobinRisk(): ?string
    {
        if ($this->hemoglobinValue === null) {
            throw new Exception('Require the `hemoglobin` value for analysis');
        }

        if ($this->age === null) {
            throw new Exception('Require the `age` value for analysis hemoglobin value');
        }

        if ($this->age < 0.1) {
            if ($this->hemoglobinValue >= 13.5 && $this->hemoglobinValue <= 24.0) {
                return 'Normal';
            } else if ($this->hemoglobinValue < 13.5) {
                return 'Low';
            } else if ($this->hemoglobinValue > 24.0) {
                return 'High';
            }
        } else if ($this->age >= 0.1 && $this->age <= 0.2) {
            if ($this->hemoglobinValue >= 10.0 && $this->hemoglobinValue <= 20.0) {
                return 'Normal';
            } else if ($this->hemoglobinValue < 10.0) {
                return 'Low';
            } else if ($this->hemoglobinValue > 20.0) {
                return 'High';
            }
        } else if ($this->age > 0.2 && $this->age <= 0.6) {
            if ($this->hemoglobinValue >= 10.0 && $this->hemoglobinValue <= 20.0) {
                return 'Normal';
            } else if ($this->hemoglobinValue < 10.0) {
                return 'Low';
            } else if ($this->hemoglobinValue > 20.0) {
                return 'High';
            }
        } else if ($this->age > 0.6 && $this->age <= 2) {
            if ($this->hemoglobinValue >= 10.5 && $this->hemoglobinValue <= 13.5) {
                return 'Normal';
            } else if ($this->hemoglobinValue < 10.5) {
                return 'Low';
            } else if ($this->hemoglobinValue > 13.5) {
                return 'High';
            }
        } else if ($this->age > 2 && $this->age <= 6) {
            if ($this->hemoglobinValue >= 11.5 && $this->hemoglobinValue <= 13.5) {
                return 'Normal';
            } else if ($this->hemoglobinValue < 11.5) {
                return 'Low';
            } else if ($this->hemoglobinValue > 13.5) {
                return 'High';
            }
        } else if ($this->age > 6 && $this->age <= 12) {
            if ($this->hemoglobinValue >= 11.5 && $this->hemoglobinValue <= 15.5) {
                return 'Normal';
            } else if ($this->hemoglobinValue < 11.5) {
                return 'Low';
            } else if ($this->hemoglobinValue > 15.5) {
                return 'High';
            }
        } else if ($this->age > 12 && $this->age <= 18) {
            if ($this->gender == 'male') {
                if ($this->hemoglobinValue >= 13.0 && $this->hemoglobinValue <= 16.0) {
                    return 'Normal';
                } else if ($this->hemoglobinValue < 13.0) {
                    return 'Low';
                } else if ($this->hemoglobinValue > 16.0) {
                    return 'High';
                }
            } else {
                if ($this->hemoglobinValue >= 12.0 && $this->hemoglobinValue <= 16.0) {
                    return 'Normal';
                } else if ($this->hemoglobinValue < 12.0) {
                    return 'Low';
                } else if ($this->hemoglobinValue > 16.0) {
                    return 'High';
                }
            }
        } else if ($this->age > 18) {
            if ($this->gender == 'male') {
                if ($this->hemoglobinValue >= 13.6 && $this->hemoglobinValue <= 17.7) {
                    return 'Normal';
                } else if ($this->hemoglobinValue < 13.6) {
                    return 'Low';
                } else if ($this->hemoglobinValue > 17.7) {
                    return 'High';
                }
            } else {
                if ($this->hemoglobinValue >= 12.1 && $this->hemoglobinValue <= 15.1) {
                    return 'Normal';
                } else if ($this->hemoglobinValue < 12.1) {
                    return 'Low';
                } else if ($this->hemoglobinValue > 15.1) {
                    return 'High';
                }
            }
        }

        return null;
    }

    
    /**
     * Get liver risk based on AST value
     * 
     * @return string
     */
    public function getLiverRisk(): string
    {
        if ($this->astValue === null) {
            throw new Exception('Require the `AST` value for analysis');
        }

        if ($this->astValue < 40) {
            return "Normal";
        } elseif ($this->astValue >= 40 && $this->astValue < 80) {
            return "Mildly Elevated";
        } elseif ($this->astValue >= 80 && $this->astValue < 160) {
            return "Moderately Elevated";
        } else {
            return "Severely Elevated";
        }
    }

    /**
     * Get liver risk based on ALT value
     * 
     * @return string
     */
    public function getALTRisk(): string
    {
        if ($this->altValue === null) {
            throw new Exception('Require the `ALT` value for analysis');
        }

        if ($this->altValue < 40) {
            return "Normal";
        } elseif ($this->altValue >= 40 && $this->altValue < 80) {
            return "Mildly Elevated";
        } elseif ($this->altValue >= 80 && $this->altValue < 160) {
            return "Moderately Elevated";
        } else {
            return "Severely Elevated";
        }
    }

    /**
     * Get kidney risk based on eGPR value
     * 
     * @return string
     */
    public function getKidneyRisk(): string
    {
        if ($this->eGprValue === null) {
            throw new Exception('Require the `eGPR` value for analysis');
        }

        if ($this->eGprValue >= 90) {
            return "Normal";
        } elseif ($this->eGprValue >= 60 && $this->eGprValue < 90) {
            return "Mildly Decreased";
        } elseif ($this->eGprValue >= 30 && $this->eGprValue < 60) {
            return "Mild to Moderate Decrease";
        } elseif ($this->eGprValue >= 15 && $this->eGprValue < 30) {
            return "Severely Decreased";
        } else {
            return "Kidney Failure";
        }
    }

    /**
     * Get total cholesterol risk based on total cholesterol value
     * 
     * @return string
     */
    public function getTotalCholesterolRisk(): string
    {
        if ($this->totalCholesterolValue === null) {
            throw new Exception('Require the `total cholesterol` value for analysis');
        }

        if ($this->totalCholesterolValue < 200) {
            return "Desirable";
        } elseif ($this->totalCholesterolValue >= 200 && $this->totalCholesterolValue < 239) {
            return "Borderline High";
        } else {
            return "High";
        }
    }

    /**
     * Get diabetes risk based on fasting blood sugar value
     * 
     * @return string
     */
    public function getLdlRisk(): string
    {
        if ($this->ldlValue === null) {
            throw new Exception('Require the `LDL` value for analysis');
        }

        if ($this->ldlValue < 100) {
            return "Optimal";
        } elseif ($this->ldlValue >= 100 && $this->ldlValue < 129) {
            return "Near Optimal";
        } elseif ($this->ldlValue >= 130 && $this->ldlValue < 159) {
            return "Borderline High";
        } elseif ($this->ldlValue >= 160 && $this->ldlValue < 189) {
            return "High";
        } else {
            return "Very High";
        }
    }

    /**
     * Get diabetes risk based on fasting blood sugar value
     * 
     * @return string
     */
    public function getHdlRisk(): string
    {
        if ($this->hdlValue === null) {
            throw new Exception('Require the `HDL` value for analysis');
        }

        if ($this->gender === 'male') {
            if ($this->hdlValue < 40) {
                return "Low";
            } elseif ($this->hdlValue >= 40 && $this->hdlValue < 60) {
                return "Normal";
            } else {
                return "Optimal";
            }
        } else {
            if ($this->hdlValue < 50) {
                return "Low";
            } elseif ($this->hdlValue >= 50 && $this->hdlValue < 60) {
                return "Normal";
            } else {
                return "Optimal";
            }
        }
    }

    /**
     * Get diabetes risk based on fasting blood sugar value
     * 
     * @return string
     */
    public function getDiabetesRisk(): string
    {
        if ($this->fsbValue === null) {
            throw new Exception('Require the `FBS` value for analysis');
        }

        if ($this->fsbValue < 100) {
            return "Normal";
        } elseif ($this->fsbValue >= 100 && $this->fsbValue < 125) {
            return "Prediabetes";
        } else {
            return "Diabetes";
        }
    }

    /**
     * Get HbA1c risk based on HbA1c value
     * 
     * @return string
     */
    public function getHba1cRisk(): string
    {
        if ($this->hba1cValue === null) {
            throw new Exception('Require the `HbA1c` value for analysis');
        }

        if ($this->hba1cValue < 5.7) {
            return "Normal";
        } elseif ($this->hba1cValue >= 5.7 && $this->hba1cValue < 6.5) {
            return "Prediabetes";
        } else {
            return "Diabetes";
        }
    }

    /**
     * Get neutral fat risk based on neutral fat value
     * 
     * @return string
     */
    public function getNeutralFatRisk(): string
    {
        if ($this->neutralFatValue === null) {
            throw new Exception('Require the `neutral fat` value for analysis');
        }

        if ($this->neutralFatValue < 150) {
            return "Normal";
        } elseif ($this->neutralFatValue >= 150 && $this->neutralFatValue < 199) {
            return "Borderline High";
        } elseif ($this->neutralFatValue >= 200 && $this->neutralFatValue < 499) {
            return "High";
        } else {
            return "Very High";
        }
    }

    /**
     * Get gamma GTP risk based on gamma GTP value
     * 
     * @return string
     */
    public function getGammaGTPRisk(): string
    {
        if ($this->gammaGTPValue === null) {
            throw new Exception('Require the `Gamma GTP` value for analysis');
        }

        if ($this->gammaGTPValue < 60) {
            return "Normal";
        } elseif ($this->gammaGTPValue >= 60 && $this->gammaGTPValue < 120) {
            return "Mildly Elevated";
        } elseif ($this->gammaGTPValue >= 120 && $this->gammaGTPValue < 200) {
            return "Moderately Elevated";
        } else {
            return "Severely Elevated";
        }
    }

    /**
     * Get hypertension risk based on systolic and diastolic blood pressure values
     * 
     * @return string
     */
    public function getHypertensionRisk(): string
    {
        if ($this->systolicBloodPressureValue === null || $this->diastolicBloodPressureValue === null) {
            throw new Exception('Require both systolic and diastolic blood pressure values for analysis hypertension risk');
        }

        if ($this->systolicBloodPressureValue < 120 && $this->diastolicBloodPressureValue < 80) {
            return "Normal";
        } elseif (($this->systolicBloodPressureValue >= 120 && $this->systolicBloodPressureValue < 130) && $this->diastolicBloodPressureValue < 80) {
            return "Elevated";
        } elseif (($this->systolicBloodPressureValue >= 130 && $this->systolicBloodPressureValue < 140) || ($this->diastolicBloodPressureValue >= 80 && $this->diastolicBloodPressureValue < 90)) {
            return "Hypertension Stage 1";
        } elseif ($this->systolicBloodPressureValue >= 140 || $this->diastolicBloodPressureValue >= 90) {
            return "Hypertension Stage 2";
        } else {
            return "Hypertensive Crisis - Consult your doctor immediately";
        }
    }

    /**
     * Get systolic blood pressure risk based on systolic blood pressure value
     * 
     * @return string
     */
    public function getSystolicBloodPressureRisk(): string
    {
        if ($this->systolicBloodPressureValue === null) {
            throw new Exception('Require systolic blood pressure values for analysis risk');
        }

        if ($this->systolicBloodPressureValue < 120) {
            return "Normal";
        } else if ($this->systolicBloodPressureValue >= 120 && $this->systolicBloodPressureValue <= 129) {
            return "Elevated";
        } else if ($this->systolicBloodPressureValue >= 130 && $this->systolicBloodPressureValue <= 139) {
            return "High Blood Pressure Stage 1";
        } else if ($this->systolicBloodPressureValue >= 140 && $this->systolicBloodPressureValue <= 179) {
            return "High Blood Pressure Stage 2";
        } else {
            return "Hypertensive Crisis - Consult your doctor immediately";
        }
    }

    /**
     * Get diastolic blood pressure risk based on diastolic blood pressure value
     * 
     * @return string
     */
    public function getDiastolicBloodPressureRisk(): string
    {
        if ($this->diastolicBloodPressureValue === null) {
            throw new Exception('Require diastolic blood pressure values for analysis risk');
        }

        if ($this->diastolicBloodPressureValue < 80) {
            return "Normal";
        } else if ($this->diastolicBloodPressureValue >= 80 && $this->diastolicBloodPressureValue <= 89) {
            return "Elevated";
        } else if ($this->diastolicBloodPressureValue >= 90 && $this->diastolicBloodPressureValue <= 119) {
            return "High Blood Pressure Stage 1";
        } else if ($this->diastolicBloodPressureValue >= 120 && $this->diastolicBloodPressureValue <= 140) {
            return "High Blood Pressure Stage 2";
        } else {
            return "Hypertensive Crisis - Consult your doctor immediately";
        }
    }

    /**
     * Get BMI (Body Mass Index) based on height and weight values
     * 
     * @return ?float
     */
    public function getBMI(): ?float
    {
        if ($this->height === null || $this->weight === null) {
            return null;
        }

        $heightInMeters = $this->height / 100;

        return round($this->weight / ($heightInMeters * $heightInMeters), 1);
    }

    /**
     * Get BMI risk based on BMI value
     * 
     * @return string
     */
    public function getBMIRisk(): string
    {
        $bmi = $this->getBMI();

        if ($bmi === null) {
            throw new Exception('Require height and weight values for BMI analysis');
        }

        if ($bmi < 18.5) {
            return "Underweight";
        } elseif ($bmi >= 18.5 && $bmi < 23) {
            return "Normal";
        } elseif ($bmi >= 23 && $bmi < 25) {
            return "Overweight";
        } elseif ($bmi >= 25 && $bmi < 30) {
            return "Obese Class 1";
        } elseif ($bmi >= 30 && $bmi < 35) {
            return "Obese Class 2";
        } else {
            return "Obese Class 3";
        }
    }

    /**
     * Get waist circumference risk based on waist circumference value and gender
     * 
     * @return string
     * 
     * @throws Exception
     */
    public function getWaistCircumferenceRisk(): string
    {
        if ($this->waistCircumference === null) {
            throw new Exception('Require waist circumference value for analysis');
        }

        if ($this->gender === null) {
            throw new Exception('Require gender value for waist circumference analysis');
        }

        if ($this->gender === 'male') {
            if ($this->waistCircumference < 90) {
                return "Normal";
            } else {
                return "Abdominal Obesity";
            }
        } else {
            if ($this->waistCircumference < 85) {
                return "Normal";
            } else {
                return "Abdominal Obesity";
            }
        }
    }

    /**
     * Get heart rate risk based on heart rate value
     * 
     * @return string
     */
    public function getHeartRateRisk(): string
    {
        if ($this->heartRate === null) {
            throw new Exception('Require heart rate value for analysis');
        }

        if ($this->heartRate < 60) {
            return "Bradycardia";
        } elseif ($this->heartRate >= 60 && $this->heartRate <= 100) {
            return "Normal";
        } else {
            return "Tachycardia";
        }
    }

    /**
     * Get uric acid risk based on uric acid value and gender
     * 
     * @return string
     */
    public function getUricAcidRisk(): string
    {
        if ($this->uricAcidValue === null) {
            throw new Exception('Require uric acid value for analysis');
        }

        if ($this->gender === null) {
            throw new Exception('Require gender value for uric acid analysis');
        }

        if ($this->gender === 'male') {
            if ($this->uricAcidValue < 3.4) {
                return "Low";
            } elseif ($this->uricAcidValue >= 3.4 && $this->uricAcidValue <= 7.0) {
                return "Normal";
            } else {
                return "High";
            }
        } else {
            if ($this->uricAcidValue < 2.4) {
                return "Low";
            } elseif ($this->uricAcidValue >= 2.4 && $this->uricAcidValue <= 6.0) {
                return "Normal";
            } else {
                return "High";
            }
        }
    }

    /**
     * Get serum creatinine risk based on serum creatinine value and gender
     * 
     * @return string
     */
    public function getSerumCreatinineRisk(): string
    {
        if ($this->serumCreatinineValue === null) {
            throw new Exception('Require serum creatinine value for analysis');
        }

        if ($this->gender === null) {
            throw new Exception('Require gender value for serum creatinine analysis');
        }

        if ($this->gender === 'male') {
            if ($this->serumCreatinineValue < 0.7) {
                return "Low";
            } elseif ($this->serumCreatinineValue >= 0.7 && $this->serumCreatinineValue <= 1.3) {
                return "Normal";
            } else {
                return "High";
            }
        } else {
            if ($this->serumCreatinineValue < 0.6) {
                return "Low";
            } elseif ($this->serumCreatinineValue >= 0.6 && $this->serumCreatinineValue <= 1.1) {
                return "Normal";
            } else {
                return "High";
            }
        }
    }

    /**
     * Generate a medical report based on the available data
     * 
     * @return string
     */
    public function getReport(): string
    {
        $reportData = [];

        if ($this->height !== null && $this->weight !== null) {
            $reportData[] = "📏 BMI : " . $this->getBMI() . " kg/m²";
            $reportData[] = "* BMI Risk : " . $this->getBMIRisk();
            $reportData[] = "";
        }

        if ($this->waistCircumference !== null && $this->gender !== null) {
            $reportData[] = "📐 Waist Circumference : " . $this->waistCircumference . " cm";
            $reportData[] = "* Waist Circumference Risk : " . $this->getWaistCircumferenceRisk();
            $reportData[] = "";
        }

        if ($this->fsbValue !== null) {
            $reportData[] = "🍬 Fasting Blood Sugar : " . $this->fsbValue . " mg/dL";
            $reportData[] = "* Diabetes Risk : " . $this->getDiabetesRisk();
            $reportData[] = "";
        }

        if ($this->hba1cValue !== null) {
            $reportData[] = "🔬 HbA1c : " . $this->hba1cValue . " %";
            $reportData[] = "* HbA1c Risk : " . $this->getHba1cRisk();
            $reportData[] = "";
        }

        if ($this->ldlValue !== null) {
            $reportData[] = "LDL (Low-Density Lipoprotein) : " . $this->ldlValue . " mg/dL";
            $reportData[] = "* LDL Risk : " . $this->getLdlRisk();
            $reportData[] = "";
        }

        if ($this->hdlValue !== null && $this->gender !== null) {
            $reportData[] = "HDL (High-Density Lipoprotein) : " . $this->hdlValue . " mg/dL";
            $reportData[] = "* HDL Risk : " . $this->getHdlRisk();
            $reportData[] = "";
        }

        if ($this->eGprValue !== null) {
            $reportData[] = "eGFR (Estimated Glomerular Filtration Rate) : " . $this->eGprValue . " mL/min/1.73m²";
            $reportData[] = "* Kidney Risk : " . $this->getKidneyRisk();
            $reportData[] = "";
        }

        if ($this->serumCreatinineValue !== null && $this->gender !== null) {
            $reportData[] = "Serum Creatinine : " . $this->serumCreatinineValue . " mg/dL";
            $reportData[] = "* Serum Creatinine Risk : " . $this->getSerumCreatinineRisk();
            $reportData[] = "";
        }

        if ($this->astValue !== null) {
            $reportData[] = "AST (Aspartate Aminotransferase) : " . $this->astValue . " U/L";
            $reportData[] = "* Liver Risk : " . $this->getLiverRisk();
            $reportData[] = "";
        }

        if ($this->altValue !== null) {
            $reportData[] = "ALT (Alanine Aminotransferase) : " . $this->altValue . " U/L";
            $reportData[] = "* ALT Risk : " . $this->getALTRisk();
            $reportData[] = "";
        }

        if ($this->gammaGTPValue !== null) {
            $reportData[] = "Gamma (γ) GTP (Glutamyl Transpeptidase) : " . $this->gammaGTPValue . " U/L";
            $reportData[] = "* Gamma GTP Risk : " . $this->getGammaGTPRisk();
            $reportData[] = "";
        }

        if ($this->neutralFatValue !== null) {
            $reportData[] = "🍔 Neutral Fat : " . $this->neutralFatValue . " mg/dL";
            $reportData[] = "* Neutral Fat Risk : " . $this->getNeutralFatRisk();
            $reportData[] = "";
        }

        if ($this->totalCholesterolValue !== null) {
            $reportData[] = "🥩 Total Cholesterol : " . $this->totalCholesterolValue . " mg/dL";
            $reportData[] = "* Total Cholesterol Risk : " . $this->getTotalCholesterolRisk();
            $reportData[] = "";
        }

        if ($this->hemoglobinValue !== null && $this->age !== null) {
            $reportData[] = "🩸 Hemoglobin : " . $this->hemoglobinValue . " g/dL";
            $reportData[] = "* Hemoglobin Risk : " . $this->getHemoglobinRisk();
            $reportData[] = "";
        }

        if ($this->uricAcidValue !== null && $this->gender !== null) {
            $reportData[] = "💎 Uric Acid : " . $this->uricAcidValue . " mg/dL";
            $reportData[] = "* Uric Acid Risk : " . $this->getUricAcidRisk();
            $reportData[] = "";
        }

        if ($this->heartRate !== null) {
            $reportData[] = "💓 Heart Rate : " . $this->heartRate . " bpm";
            $reportData[] = "* Heart Rate Risk : " . $this->getHeartRateRisk();
            $reportData[] = "";
        }

        if ($this->systolicBloodPressureValue !== null && $this->diastolicBloodPressureValue !== null) {
            $reportData[] = "❣️ Blood Pressure : " . $this->systolicBloodPressureValue . "/" . $this->diastolicBloodPressureValue . " mmHg";
            $reportData[] = "* Systolic Blood Pressure Risk : " . $this->getSystolicBloodPressureRisk();
            $reportData[] = "* Diastolic Blood Pressure Risk : " . $this->getDiastolicBloodPressureRisk();
            $reportData[] = "* Hypertension Risk : " . $this->getHypertensionRisk();
            $reportData[] = "";
        }

        return join(PHP_EOL, $reportData);
    }

    /**
     * Convert the health data to an array format
     * 
     * @return array
     */
    public function toArray(): array
    {
        $data = [];

        if ($this->height !== null && $this->weight !== null) {
            $data['bmi'] = ['value' => $this->getBMI(), 'risk' => $this->getBMIRisk()];
        }

        if ($this->fsbValue !== null) {
            $data['fbs'] = ['value' => $this->fsbValue, 'risk' => $this->getDiabetesRisk()];
        }

        if ($this->hba1cValue !== null) {
            $data['hba1c'] = ['value' => $this->hba1cValue, 'risk' => $this->getHba1cRisk()];
        }

        if ($this->ldlValue !== null) {
            $data['ldl'] = ['value' => $this->ldlValue, 'risk' => $this->getLdlRisk()];
        }

        if ($this->hdlValue !== null && $this->gender !== null) {
            $data['hdl'] = ['value' => $this->hdlValue, 'risk' => $this->getHdlRisk()];
        }

        if ($this->totalCholesterolValue !== null) {
            $data['totalCholesterol'] = ['value' => $this->totalCholesterolValue, 'risk' => $this->getTotalCholesterolRisk()];
        }

        if ($this->eGprValue !== null) {
            $data['egfr'] = ['value' => $this->eGprValue, 'risk' => $this->getKidneyRisk()];
        }

        if ($this->astValue !== null) {
            $data['ast'] = ['value' => $this->astValue, 'risk' => $this->getLiverRisk()];
        }

        if ($this->systolicBloodPressureValue !== null && $this->diastolicBloodPressureValue !== null) {
            $data['bloodPressure'] = [
                'systolic' => $this->systolicBloodPressureValue,
                'diastolic' => $this->diastolicBloodPressureValue,
                'risk' => $this->getHypertensionRisk()
            ];
        }

        return $data;
    }
}
