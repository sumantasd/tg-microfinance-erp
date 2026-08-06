<?php

namespace App\Models;

namespace App\Services;

use App\Models\Employee;

class HrLetterService
{
    /**
     * Generate HR document structure based on type and employee profile.
     */
    public function generateLetter(Employee $employee, string $type): array
    {
        $company = $employee->company;
        $branch = $employee->branch;
        $designation = $employee->designation;
        $department = $employee->department;

        $dateStr = now()->format('F d, Y');
        $refNo = 'TG-HR/' . strtoupper($type) . '/' . date('Y') . '/' . str_pad($employee->id, 4, '0', STR_PAD_LEFT);

        switch ($type) {
            case 'offer_letter':
                $title = 'OFFER OF EMPLOYMENT';
                $subject = 'Offer Letter for the Position of ' . ($designation->title ?? 'Executive');
                $content = "Dear <strong>{$employee->full_name}</strong>,<br><br>"
                    . "We are pleased to offer you the position of <strong>{$designation->title}</strong> in the <strong>{$department->name}</strong> department at <strong>{$company->name}</strong>, located at our <strong>{$branch->name}</strong> branch office.<br><br>"
                    . "Your basic monthly compensation will be <strong>₹" . number_format($employee->basic_salary, 2) . "</strong> per month. Your anticipated joining date is <strong>" . ($employee->joining_date ? $employee->joining_date->format('F d, Y') : $dateStr) . "</strong>.<br><br>"
                    . "Please sign and return the duplicate copy of this letter as a token of your acceptance of this offer.";
                break;

            case 'appointment_letter':
                $title = 'LETTER OF APPOINTMENT';
                $subject = 'Appointment Letter - Employee Code: ' . $employee->employee_code;
                $content = "Dear <strong>{$employee->full_name}</strong>,<br><br>"
                    . "Further to your acceptance of our offer, we are delighted to formally appoint you as <strong>{$designation->title}</strong> at <strong>{$company->name}</strong> with effect from <strong>" . ($employee->joining_date ? $employee->joining_date->format('F d, Y') : $dateStr) . "</strong>.<br><br>"
                    . "You will be posted at <strong>{$branch->name}</strong> branch, located at {$branch->address}, {$branch->city}, {$branch->state} - {$branch->pincode}.<br><br>"
                    . "You will be on probation for a period of six months from your date of joining. Your employment will be governed by the standard policies, rules, and regulations of TG Microfinance ERP.";
                break;

            case 'experience_certificate':
                $title = 'EXPERIENCE & SERVICE CERTIFICATE';
                $subject = 'Service Certificate for ' . $employee->full_name;
                $content = "TO WHOMSOEVER IT MAY CONCERN<br><br>"
                    . "This is to certify that <strong>{$employee->full_name}</strong> (Employee Code: <strong>{$employee->employee_code}</strong>) was employed with <strong>{$company->name}</strong> from <strong>" . ($employee->joining_date ? $employee->joining_date->format('F d, Y') : 'N/A') . "</strong> to <strong>" . date('F d, Y') . "</strong>.<br><br>"
                    . "During their tenure, they served as <strong>{$designation->title}</strong> at our <strong>{$branch->name}</strong> branch.<br><br>"
                    . "We found them to be hardworking, dedicated, and professional. We wish them all the best in their future endeavors.";
                break;

            case 'relieving_letter':
                $title = 'RELIEVING LETTER';
                $subject = 'Relieving Letter & Full Settlement Confirmation';
                $content = "Dear <strong>{$employee->full_name}</strong>,<br><br>"
                    . "With reference to your resignation, we hereby accept your resignation and relieve you from your duties as <strong>{$designation->title}</strong> at <strong>{$company->name}</strong> effective end of day <strong>" . date('F d, Y') . "</strong>.<br><br>"
                    . "We confirm that you have completed all handover procedures and settled all outstanding dues with our <strong>{$branch->name}</strong> branch.<br><br>"
                    . "We appreciate your contributions during your service with us.";
                break;

            default:
                throw new \InvalidArgumentException("Invalid HR Letter Type: {$type}");
        }

        return [
            'type' => $type,
            'title' => $title,
            'subject' => $subject,
            'ref_no' => $refNo,
            'date' => $dateStr,
            'content' => $content,
            'employee' => $employee,
            'company' => $company,
            'branch' => $branch,
        ];
    }
}
