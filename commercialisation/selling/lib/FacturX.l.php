<?php
namespace selling;

// Lors du réimport de FPDI, modifier l'autoload.php pour rajouter l'argument prepend à true dans spl_autoload_register
require_once __DIR__.'/FPDI-2.6.3/src/autoload.php';
require_once __DIR__.'/FPDF-1.8.6/fpdf.php';
require_once __DIR__.'/factur-x-2.3.1/src/Fpdi/FdpiFacturx.php';
require_once __DIR__.'/factur-x-2.3.1/src/Utils/ProfileHandler.php';
require_once __DIR__.'/factur-x-2.3.1/src/XsdValidator.php';
require_once __DIR__.'/factur-x-2.3.1/src/Reader.php';
require_once __DIR__.'/factur-x-2.3.1/src/Writer.php';

class FacturXLib {

	public static function generateInvoiceXml(Invoice $eInvoice): string {

		// minimum : 'urn:factur-x.eu:1p0:minimum'
		// basic wl : 'urn:factur-x.eu:1p0:basicwl'
		$dataProfile = 'urn:factur-x.eu:1p0:basicwl';

		if($eInvoice->isCreditNote()) {
			$typeCode = '381';
		} else {
			$typeCode = '380';
		}

		if($eInvoice['farm']['configuration']['invoiceVat']) {
			$siren = mb_substr($eInvoice['farm']['configuration']['invoiceVat'], 4);
		} else {
			$siren = '';
		}

		$xml = '<?xml version="1.0" encoding="utf-8"?>
<rsm:CrossIndustryInvoice xmlns:qdt="urn:un:unece:uncefact:data:standard:QualifiedDataType:100"
xmlns:ram="urn:un:unece:uncefact:data:standard:ReusableAggregateBusinessInformationEntity:100"
xmlns:rsm="urn:un:unece:uncefact:data:standard:CrossIndustryInvoice:100"
xmlns:udt="urn:un:unece:uncefact:data:standard:UnqualifiedDataType:100"
xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
	<rsm:ExchangedDocumentContext><!--BG-2-->
		<ram:GuidelineSpecifiedDocumentContextParameter><!--BT-24-00-->
			<ram:ID>'.$dataProfile.'</ram:ID><!--BT-24-->
		</ram:GuidelineSpecifiedDocumentContextParameter>
	</rsm:ExchangedDocumentContext>
	<rsm:ExchangedDocument>
		<ram:ID>'.encode($eInvoice['name']).'</ram:ID><!--BT-1-->
		<ram:TypeCode>'.$typeCode.'</ram:TypeCode><!--BT-3-->
		<ram:IssueDateTime><!--BT-2-00-->
			<udt:DateTimeString format="102">'.date('Ymd', strtotime($eInvoice['date'])).'</udt:DateTimeString>
		</ram:IssueDateTime>
		<ram:IncludedNote><!--BG-1-->
			<ram:Content></ram:Content><!--BT-22-->
			<ram:SubjectCode>AAB</ram:SubjectCode><!--BT-21 AAB = escompte-->
		</ram:IncludedNote>
		<ram:IncludedNote><!--BG-1-->
			<ram:Content></ram:Content><!--BT-22-->
			<ram:SubjectCode>PMT</ram:SubjectCode><!--BT-21 PMT = frais de recouvrement-->
		</ram:IncludedNote>
		<ram:IncludedNote><!--BG-1-->
			<ram:Content></ram:Content><!--BT-22-->
			<ram:SubjectCode>PMD</ram:SubjectCode><!--BT-21 PMD = pénalité de retard-->
		</ram:IncludedNote>
	</rsm:ExchangedDocument>
	<rsm:SupplyChainTradeTransaction><!--BG-25-00-->
		<ram:ApplicableHeaderTradeAgreement><!--BT-10-00-->
			<ram:SellerTradeParty><!--BG-4-->
				<ram:Name>'.encode($eInvoice['farm']['legalName']).'</ram:Name><!--BT-24-->
				'.($eInvoice['farm']['configuration']['invoiceVat'] !== NULL ? '
				<ram:SpecifiedLegalOrganization><!--BT-30-->
					<ram:ID schemeID="0002">'.encode($siren).'</ram:ID>
				</ram:SpecifiedLegalOrganization>' : '').'
				<ram:PostalTradeAddress><!--BG-5-->
					<ram:CountryID>FR</ram:CountryID><!--BT-40-->
				</ram:PostalTradeAddress>
				<ram:URIUniversalCommunication><!--BT-34-00-->
					<ram:URIID schemeID="9957">'.encode($eInvoice['farm']['configuration']['invoiceVat']).'</ram:URIID><!--BT-34-->
				</ram:URIUniversalCommunication>
				'.($eInvoice['farm']['configuration']['invoiceVat'] !== NULL ? '
				<ram:SpecifiedTaxRegistration><!--BT-31-00-->
					<ram:ID schemeID="VA">'.encode($eInvoice['farm']['configuration']['invoiceVat']).'</ram:ID><!--BT-31-->
				</ram:SpecifiedTaxRegistration>' : '').'
			</ram:SellerTradeParty>
			<ram:BuyerTradeParty><!--BG-7-->
				<ram:Name>'.encode($eInvoice['customer']['legalName'] ?? $eInvoice['customer']['name']).'</ram:Name><!--BT-44-->
				'.($eInvoice['customer']['siret'] !== NULL ? '
				<ram:SpecifiedLegalOrganization><!--BT-47-00-->
					<ram:ID schemeID="0002">'.encode($eInvoice['customer']['siret']).'</ram:ID>
				</ram:SpecifiedLegalOrganization>' : '').'
				<ram:PostalTradeAddress><!--BG-8-->
					<ram:CountryID>FR</ram:CountryID><!--BT-55-->
				</ram:PostalTradeAddress>
				<ram:URIUniversalCommunication><!--BT-49-00-->
					<ram:URIID schemeID="9957">'.encode($eInvoice['customer']['invoiceVat']).'</ram:URIID><!--BT-49-->
				</ram:URIUniversalCommunication>
			</ram:BuyerTradeParty>
		</ram:ApplicableHeaderTradeAgreement>
		<ram:ApplicableHeaderTradeDelivery/><!--BG-13-00-->
		<ram:ApplicableHeaderTradeSettlement><!--BG-19-00-->
			<ram:InvoiceCurrencyCode>EUR</ram:InvoiceCurrencyCode><!--BT-5-->';
			foreach($eInvoice['vatByRate'] as $vatByRate) {
				$xml .= '
				<ram:ApplicableTradeTax><!--BG-23-->
					<ram:CalculatedAmount>'.$vatByRate['vat'].'</ram:CalculatedAmount>
					<ram:TypeCode>VAT</ram:TypeCode>
					<ram:BasisAmount>'.$vatByRate['amount'].'</ram:BasisAmount>
					<ram:CategoryCode>S</ram:CategoryCode>
					<ram:RateApplicablePercent>'.$vatByRate['vatRate'].'</ram:RateApplicablePercent>
				</ram:ApplicableTradeTax>
			';
			}
		$xml .= '
			<ram:SpecifiedTradePaymentTerms><!--BT-20 : Payment terms-->
				<ram:Description>
					'.encode($eInvoice['farm']['configuration']['invoicePaymentCondition'] ?? '').'
				</ram:Description>
			</ram:SpecifiedTradePaymentTerms>
			<ram:SpecifiedTradeSettlementHeaderMonetarySummation><!--BG-22-->
        <ram:LineTotalAmount>'.$eInvoice['priceExcludingVat'].'</ram:LineTotalAmount><!--BT-106-->
				<ram:TaxBasisTotalAmount>'.$eInvoice['priceExcludingVat'].'</ram:TaxBasisTotalAmount><!--BT-109-->
				<ram:TaxTotalAmount currencyID="EUR">'.$eInvoice['vat'].'</ram:TaxTotalAmount><!--BT-110-->
				<ram:GrandTotalAmount>'.$eInvoice['priceIncludingVat'].'</ram:GrandTotalAmount><!--BT-112-->
				<ram:DuePayableAmount>'.$eInvoice['priceIncludingVat'].'</ram:DuePayableAmount><!--BT-115-->
			</ram:SpecifiedTradeSettlementHeaderMonetarySummation>
		</ram:ApplicableHeaderTradeSettlement>
	</rsm:SupplyChainTradeTransaction>
</rsm:CrossIndustryInvoice>';

		return $xml;
	}

	/**
	 * Note : il faut que le fichier PDF soit à un format <= 1.4.
	 * Transformation possible via cette commande : gs -sDEVICE=pdfwrite -dCompatibilityLevel=1.4 -o output.pdf input.pdf
	 * input.pdf et output.pdf doivent être différents
	 */
	public static function generate(Invoice $eInvoice, string $pdfContent): string {

		$eInvoice->expects(['name', 'date', 'priceExcludingVat', 'vat', 'priceIncludingVat', 'farm' => ['id', 'siret'], 'customer' => ['name']]);

		$eInvoice['farm']['configuration'] =  \selling\ConfigurationLib::getByFarm($eInvoice['farm']);

		$originalInvoiceFilepath = '/tmp/'.$eInvoice['id'].'-1.7.pdf';
		file_put_contents($originalInvoiceFilepath, $pdfContent);
		$transformedInvoiceFilepath = '/tmp/'.$eInvoice['id'].'-1.4.pdf';

		$writer = new \Atgp\FacturX\Writer();

		exec('gs -sDEVICE=pdfwrite -dCompatibilityLevel=1.4 -o '.$transformedInvoiceFilepath.' '.$originalInvoiceFilepath);

		$pdfContent = file_get_contents($transformedInvoiceFilepath);

		$xmlContent = self::generateInvoiceXml($eInvoice);

		unlink($originalInvoiceFilepath);
		unlink($transformedInvoiceFilepath);

		return $writer->generate($pdfContent, $xmlContent);

	}

}
