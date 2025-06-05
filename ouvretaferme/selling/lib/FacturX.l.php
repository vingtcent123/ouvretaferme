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
		$dataProfile = 'urn:factur-x.eu:1p0:minimum';

		if($eInvoice->isCreditNote()) {
			$typeCode = '381';
		} else {
			$typeCode = '380';
		}

		return '<?xml version="1.0" encoding="utf-8"?>
<rsm:CrossIndustryInvoice xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:qdt="urn:un:unece:uncefact:data:standard:QualifiedDataType:100" xmlns:udt="urn:un:unece:uncefact:data:standard:UnqualifiedDataType:100" xmlns:rsm="urn:un:unece:uncefact:data:standard:CrossIndustryInvoice:100" xmlns:ram="urn:un:unece:uncefact:data:standard:ReusableAggregateBusinessInformationEntity:100" xsi:schemaLocation="urn:gs1:uncefact:data:standard:CrossIndustryInvoice:1 ./data/standard/CrossIndustryInvoice_1p0.xsd">
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
	</rsm:ExchangedDocument>
	<rsm:SupplyChainTradeTransaction><!--BG-25-00-->
		<ram:ApplicableHeaderTradeAgreement><!--BT-10-00-->
			<ram:SellerTradeParty><!--BG-4-->
				<ram:Name>'.encode($eInvoice['farm']['configuration']['legalName']).'</ram:Name><!--BT-24-->
				'.($eInvoice['farm']['configuration']['invoiceRegistration'] !== NULL ? '
				<ram:SpecifiedLegalOrganization><!--BT-30-->
					<ram:ID schemeID="0002">'.encode($eInvoice['farm']['configuration']['invoiceRegistration']).'</ram:ID>
				</ram:SpecifiedLegalOrganization>' : '').'
				<ram:PostalTradeAddress><!--BG-5-->
					<ram:CountryID>FR</ram:CountryID><!--BT-40-->
				</ram:PostalTradeAddress>
				'.($eInvoice['farm']['configuration']['invoiceVat'] !== NULL ? '
				<ram:SpecifiedTaxRegistration><!--BT-31-00-->
					<ram:ID schemeID="VA">'.encode($eInvoice['farm']['configuration']['invoiceVat']).'</ram:ID><!--BT-31-->
				</ram:SpecifiedTaxRegistration>' : '').'
			</ram:SellerTradeParty>
			<ram:BuyerTradeParty><!--BG-7-->
				<ram:Name>'.encode($eInvoice['customer']['legalName'] ?? $eInvoice['customer']['name']).'</ram:Name><!--BT-44-->
				'.($eInvoice['customer']['invoiceRegistration'] !== NULL ? '
				<ram:SpecifiedLegalOrganization><!--BT-47-00-->
					<ram:ID schemeID="0002">'.encode($eInvoice['customer']['invoiceRegistration']).'</ram:ID>
				</ram:SpecifiedLegalOrganization>' : '').'
			</ram:BuyerTradeParty>
		</ram:ApplicableHeaderTradeAgreement>
		<ram:ApplicableHeaderTradeDelivery/><!--BG-13-00-->
		<ram:ApplicableHeaderTradeSettlement><!--BG-19-00-->
			<ram:InvoiceCurrencyCode>EUR</ram:InvoiceCurrencyCode><!--BT-5-->
			<ram:SpecifiedTradeSettlementHeaderMonetarySummation><!--BG-22-->
				<ram:TaxBasisTotalAmount>'.$eInvoice['priceExcludingVat'].'</ram:TaxBasisTotalAmount><!--BT-109-->
				<ram:TaxTotalAmount currencyID="EUR">'.$eInvoice['vat'].'</ram:TaxTotalAmount><!--BT-110-->
				<ram:GrandTotalAmount>'.$eInvoice['priceIncludingVat'].'</ram:GrandTotalAmount><!--BT-112-->
				<ram:DuePayableAmount>'.$eInvoice['priceIncludingVat'].'</ram:DuePayableAmount><!--BT-115-->
			</ram:SpecifiedTradeSettlementHeaderMonetarySummation>
		</ram:ApplicableHeaderTradeSettlement>
	</rsm:SupplyChainTradeTransaction>
</rsm:CrossIndustryInvoice>';

	}

	/**
	 * Note : il faut que le fichier PDF soit à un format <= 1.4.
	 * Transformation possible via cette commande : gs -sDEVICE=pdfwrite -dCompatibilityLevel=1.4 -o output.pdf input.pdf
	 * input.pdf et output.pdf doivent être différents
	 */
	public static function generate(Invoice $eInvoice, string $pdfContent): string {

		$eInvoice->expects(['name', 'date', 'priceExcludingVat', 'vat', 'priceIncludingVat', 'farm' => ['id'], 'customer' => ['name']]);

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
