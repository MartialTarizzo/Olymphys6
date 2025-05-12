<?php

namespace App\Service;


use App\Entity\Odpf\OdpfArticle;
use App\Entity\Odpf\OdpfCategorie;
use App\Entity\Odpf\OdpfEditionsPassees;
use App\Entity\Odpf\OdpfEquipesPassees;
use Doctrine\ORM\EntityManagerInterface;
use Fpdf\Fpdf;

class CreateInvitationPdf
{
    public function createInvitationPdf($quidam, $numEdition): Fpdf

    {
        $nom = $quidam[0];
        $prenom = $quidam[1];
        if ($_SERVER['SERVER_NAME'] == 'www.olymphys.fr') {
            $path = 'https://www.olymphys.fr/public/odpf/odpf-images/';
        };
        if (str_contains($_SERVER['SERVER_NAME'], 'olympessais.')) {
            $path = 'https://www.olymphys.fr/public/odpf/odpf-images/';
        };
        if ($_SERVER['SERVER_NAME'] == '127.0.0.1' or $_SERVER['SERVER_NAME'] == 'localhost') {
            $path = 'odpf/odpf-images/';
        }
        $pdf = new Fpdf('P', 'mm', 'A4');
        //$pdf->AddFont('Verdana');
        $pdf->SetFont('helvetica', '', 14);
        $pdf->SetMargins(20, 20);
        $pdf->SetLeftMargin(20);
        $pdf->SetRightMargin(20);
        $pdf->AddPage();
        $pdf->image($path . 'logo-udppc.png', 20, null, 40);
        $pdf->image($path . 'logo-sfp.png', 170, 20, 20);
        $pdf->image($path . 'site-logo-398x106.png', 75, 40, 60);
        $y = $pdf->GetY() + 30;
        $pdf->setY($y);
        //$str = 'Paris le ' . $this->date_in_french($edition->getConcoursCia()->format('Y-m-d'));
        $pdf->SetTextColor(0, 0, 0);
        $str = 'Document indispensable pour être autorisé à entrer dans l\'enceinte de  l\'université';
        $str_ = 'pour assister ou participer aux Olympiades de Physique France';
        $strprim = 'A présenter au gardien sur support papier ou en version numérique';
        $str = iconv('UTF-8', 'ISO-8859-1', $str);
        $str_ = iconv('UTF-8', 'ISO-8859-1', $str_);
        $strprim = iconv('UTF-8', 'ISO-8859-1', $strprim);
        $pdf->Cell(0, 10, $str . "\n", 0, 0, 'C');
        $y = $pdf->GetY() + 8;
        $pdf->setY($y);
        $pdf->Cell(0, 10, $str_ . "\n", 0, 0, 'C');
        $y = $pdf->GetY() + 8;
        $pdf->setY($y);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Cell(0, 10, '(' . $strprim . ')' . "\n", 0, 0, 'C');
        $pdf->SetFont('helvetica', 'B', 18);
        $str1 = 'Invitation aux ';
        $x = $pdf->GetX();
        $y = $pdf->getY() + 30;
        $w = $pdf->GetStringWidth($str1);
        $x = (210 - $w) / 2;
        $pdf->SetXY($x, $y);
        $pdf->Cell($w, 20, $str1 . "\n", 0, 0, 'C');
        $pdf->SetFont('helvetica', 'B', 18);
        $w2 = $pdf->getStringWidth($numEdition . 'e Olympiades de Physique France');//pour connaître la longueur de la chaîne
        $x = (210 - $w2) / 2;//Calcule l'emplacement
        $str2 = $numEdition;//Définit str2
        $str21 = 'Olympiades de Physique France';//Définit str21
        $w3 = $pdf->getStringWidth($numEdition);//Pour connaître la longueur de la chaîne
        $y = $pdf->getY() + 10;//clacule l'emplacment
        $pdf->SetXY($x, $y);//fixe le curseur à cet emplaecment
        $pdf->Cell($w3, 20, $str2 . "\n", 0, 0, 'L');
        $x = $pdf->GetX();
        $y = $pdf->getY() - 2;
        $pdf->SetXY($x, $y);

        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(5, 20, 'e', 0, 0, 'L');
        $x = $pdf->GetX();
        $y = $pdf->getY() + 2;
        $pdf->SetXY($x, $y);
        $pdf->SetFont('helvetica', 'B', 18);
        $pdf->Cell(0, 20, $str21 . "\n", 0, 0, 'L');
        $x = $pdf->GetX();
        $y = $pdf->getY() + 15;
        $pdf->SetXY($x, $y);
        $pdf->SetFont('helvetica', '', 14);
        $str3 = iconv('UTF-8', 'windows-1252', 'Le comité national des Olympiades de Physique France invite :');
        $x = $pdf->GetX();
        $y = $pdf->getY() + 10;
        $pdf->SetXY(0, $y);
        $pdf->Cell(0, 10, $str3 . "\n", 0, 0, 'C');
        $w4 = $pdf->getStringWidth(iconv('UTF-8', 'windows-1252', $nom . ' ' . $prenom));
        //$str4 = iconv('UTF-8', 'windows-1252', $civilite);
        $str5 = iconv('UTF-8', 'windows-1252', $prenom . ' ' . $nom);
        $x = (210 - $w4) / 2;
        //$w5 = $pdf->getStringWidth($civilite);
        $y = $pdf->getY() + 10;
        $pdf->SetXY($x, $y);
        $pdf->SetTextColor(84, 173, 209);
        $pdf->SetFont('helvetica', 'B', 18);
        $pdf->Cell($w4 - 2, 10, $str5 . "\n", 0, 0, 'L');
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont('helvetica', '', 14);
        //$x = $pdf->getX() - 4;
        $pdf->setX($x);

        //$pdf->cell(0, 10, $str5, '', 'L');
        $pdf->SetFont('helvetica', '', 14);
        $y = $pdf->getY() + 10;
        $w14 = $pdf->getStringWidth(iconv('UTF-8', 'windows-1252', 'à assister  au concours national '));
        $w15 = $pdf->getStringWidth(iconv('UTF-8', 'windows-1252', 'à l’Université d’Aix-Marseille, campus Saint-Charles'));
        $pdf->SetXY((210 - $w14) / 2, $y);
        $pdf->Cell($w14, 8, iconv('UTF-8', 'windows-1252',
            'à assister au concours national'), '', 'R');
        $x = $pdf->GetX();
        $y = $y + 10;
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->setXY((210 - $w15) / 2, $y);
        $pdf->Cell($w15, 8, iconv('UTF-8', 'windows-1252', 'à l’Université d’Aix-Marseille, campus Saint-Charles,'), '', 'C');
        $y = $pdf->GetY();
        $w16 = $pdf->getStringWidth(iconv('UTF-8', 'windows-1252', '3 place Victor Hugo Marseille 13003.'));
        $pdf->setXY((210 - $w16) / 2, $y);
        $pdf->Cell($w16, 8, iconv('UTF-8', 'windows-1252', '3 place Victor Hugo Marseille 13003.'), '', 'C');
        $pdf->SetFont('helvetica', '', 14);
        $w13 = $pdf->getStringWidth(iconv('UTF-8', 'windows-1252', 'pour le comité national des Olympiades de Physique France'));
        $x = (210 - $w13) / 2;
        $y = $pdf->getY();
        $pdf->setXY($x, $y + 12);
        $pdf->Cell($w13, 8, iconv('UTF-8', 'windows-1252', 'Pour le comité national des Olympiades de Physique France'), '', 'R');
        $y = $pdf->getY();
        $pdf->image($path . 'signature_gd_format.png', 130, $y, 40);
        $y = $pdf->getY();
        $pdf->setXY(130, $y + 20);
        $pdf->Cell(0, 8, iconv('UTF-8', 'windows-1252', 'Pascale Hervé'), '', 'C');


        return $pdf;


    }

}