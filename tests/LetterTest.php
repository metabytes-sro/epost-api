<?php

declare(strict_types=1);

namespace MetabytesSRO\EPost\Api\Tests;

use MetabytesSRO\EPost\Api\Attachment;
use MetabytesSRO\EPost\Api\Exception\ValidationException;
use MetabytesSRO\EPost\Api\Letter;
use MetabytesSRO\EPost\Api\PlugIn\Automover;
use MetabytesSRO\EPost\Api\PlugIn\PremiumAdress;
use MetabytesSRO\EPost\Api\PlugIn\PremiumAdressVariant;
use MetabytesSRO\EPost\Api\PlugIn\UploadManagement;
use MetabytesSRO\EPost\Api\Recipient;
use MetabytesSRO\EPost\Api\RegisteredMailType;
use MetabytesSRO\EPost\Api\SenderAddress;
use MetabytesSRO\EPost\Api\TestOptions;
use PHPUnit\Framework\Attributes\DataProvider;

class LetterTest extends ApiTestCase
{
    public function testMinimalPayload(): void
    {
        $payload = self::letter()->toPayload();

        self::assertSame([
            'addressLine1' => 'Max Mustermann',
            'addressLine2' => 'Musterstraße 1',
            'zipCode' => '53115',
            'city' => 'Bonn',
            'fileName' => 'letter.pdf',
            'data' => chunk_split(base64_encode(self::PDF)),
            'isColor' => false,
            'isDuplex' => false,
            'coverLetter' => false,
        ], $payload);
    }

    public function testFullPayload(): void
    {
        $cover = Attachment::fromString('%PDF-1.4 cover', 'cover.pdf');
        $sender = SenderAddress::fromCompleteLine('Fa. Huber GmbH, Am Weg 1, 76887 Bad Bergzabern');
        $test = new TestOptions('test@example.com', true);
        $letter = (new Letter())
            ->recipient(self::recipient())
            ->document(self::document())
            ->coverSheet($cover)
            ->color()
            ->duplex()
            ->batchId(4711)
            ->custom(1, 'RE-1')
            ->custom(5, 'five')
            ->costCenter('KST01')
            ->vendorSystemInformation('my-erp 1.2')
            ->sender($sender)
            ->test($test)
            ->plugIn(UploadManagement::dueInDays(2))
            ->plugIn(new PremiumAdress(PremiumAdressVariant::Report))
            ->duplicateFailsafe();

        $payload = $letter->toPayload();

        self::assertSame(self::recipient()->toArray(), array_intersect_key($payload, self::recipient()->toArray()));
        self::assertSame('letter.pdf', $payload['fileName']);
        self::assertTrue($payload['isColor']);
        self::assertTrue($payload['isDuplex']);
        self::assertTrue($payload['coverLetter']);
        self::assertSame(chunk_split(base64_encode('%PDF-1.4 cover')), $payload['coverData']);
        self::assertArrayNotHasKey('registeredLetter', $payload);
        self::assertSame(4711, $payload['batchID']);
        self::assertSame('RE-1', $payload['custom1']);
        self::assertSame('five', $payload['custom5']);
        self::assertArrayNotHasKey('custom2', $payload);
        self::assertSame('KST01', $payload['costCenter']);
        self::assertSame('my-erp 1.2', $payload['vendorSystemInformation']);
        self::assertSame('Fa. Huber GmbH, Am Weg 1, 76887 Bad Bergzabern', $payload['senderAdressLineComplete']);
        self::assertTrue($payload['testFlag']);
        self::assertSame('test@example.com', $payload['testEMail']);
        self::assertTrue($payload['testShowRestrictedArea']);
        self::assertSame([
            ['plugInName' => 'UploadManagement', 'plugInModel' => ['useMinimumQuantity' => false, 'dueDays' => 2]],
            ['plugInName' => 'PremiumAdress', 'plugInModel' => ['productVariants' => 'Report']],
        ], $payload['plugInList']);
        self::assertTrue($payload['activateDuplicateFailsafe']);

        self::assertSame($cover, $letter->getCoverSheet());
        self::assertTrue($letter->hasCoverSheet());
        self::assertTrue($letter->isColor());
        self::assertTrue($letter->isDuplex());
        self::assertNull($letter->getRegisteredMail());
        self::assertSame(4711, $letter->getBatchId());
        self::assertSame('RE-1', $letter->getCustom(1));
        self::assertNull($letter->getCustom(2));
        self::assertSame('KST01', $letter->getCostCenter());
        self::assertSame('my-erp 1.2', $letter->getVendorSystemInformation());
        self::assertSame($sender, $letter->getSender());
        self::assertSame($test, $letter->getTest());
        self::assertCount(2, $letter->getPlugIns());
        self::assertTrue($letter->hasDuplicateFailsafe());
    }

    public function testRegisteredMail(): void
    {
        $payload = self::letter()->registeredMail(RegisteredMailType::ReturnReceipt)->toPayload();

        self::assertSame('Einschreiben Rückschein', $payload['registeredLetter']);
    }

    public function testGeneratedCoverSheet(): void
    {
        $letter = self::letter()->generateCoverSheet();

        self::assertTrue($letter->toPayload()['coverLetter']);
        self::assertArrayNotHasKey('coverData', $letter->toPayload());
        self::assertNull($letter->getCoverSheet());
    }

    public function testCoverSheetCanBeRemoved(): void
    {
        $letter = self::letter()->coverSheet(Attachment::fromString('%PDF-1.4 c', 'c.pdf'))->generateCoverSheet(false);

        self::assertFalse($letter->hasCoverSheet());
        self::assertNull($letter->getCoverSheet());
        self::assertFalse($letter->toPayload()['coverLetter']);

        $letter->coverSheet(Attachment::fromString('%PDF-1.4 c', 'c.pdf'))->coverSheet(null);
        self::assertFalse($letter->hasCoverSheet());
    }

    public function testCustomFieldCanBeCleared(): void
    {
        $letter = self::letter()->custom(3, 'x')->custom(3, null)->custom(2, 'y')->custom(2, '');

        self::assertNull($letter->getCustom(3));
        self::assertNull($letter->getCustom(2));
        self::assertArrayNotHasKey('custom3', $letter->toPayload());
    }

    public function testPlugInOfSameNameReplacesEarlierOne(): void
    {
        $letter = self::letter()->plugIn(UploadManagement::dueInDays(1))->plugIn(UploadManagement::dueInDays(9));

        self::assertCount(1, $letter->getPlugIns());
        self::assertSame(['useMinimumQuantity' => false, 'dueDays' => 9], $letter->getPlugIns()[0]->model());
    }

    public function testEmptyOptionalStringsAreOmitted(): void
    {
        $payload = self::letter()->costCenter('')->vendorSystemInformation('')->toPayload();

        self::assertArrayNotHasKey('costCenter', $payload);
        self::assertArrayNotHasKey('vendorSystemInformation', $payload);
    }

    public function testGetters(): void
    {
        $letter = new Letter();

        self::assertNull($letter->getRecipient());
        self::assertNull($letter->getDocument());
        self::assertFalse($letter->hasDuplicateFailsafe());
        self::assertSame([], $letter->getPlugIns());
        self::assertNull($letter->getSender());
        self::assertNull($letter->getTest());
        self::assertSame(self::recipient()->toArray(), $letter->recipient(self::recipient())->getRecipient()?->toArray());
        self::assertSame('letter.pdf', $letter->document(self::document())->getDocument()?->fileName);
    }

    /**
     * @return iterable<string, array{callable(): mixed, string}>
     */
    public static function invalid(): iterable
    {
        yield 'no recipient' => [static fn() => (new Letter(null, self::document()))->toPayload(), 'A recipient is required'];
        yield 'no document' => [static fn() => (new Letter(self::recipient()))->toPayload(), 'A PDF document is required'];
        yield 'document too large' => [
            static fn() => (new Letter(self::recipient(), self::document('big.pdf', '%PDF-' . str_repeat('x', Attachment::MAX_LETTER_BYTES))))->toPayload(),
            'the API accepts at most 20971520 bytes',
        ];
        yield 'cover sheet too large' => [
            static fn() => self::letter()->coverSheet(self::document('cover.pdf', '%PDF-' . str_repeat('x', Attachment::MAX_COVER_SHEET_BYTES)))->toPayload(),
            'cover sheet "cover.pdf"',
        ];
        yield 'registered duplex' => [
            static fn() => self::letter()->registeredMail(RegisteredMailType::Standard)->duplex()->toPayload(),
            'E312',
        ];
        yield 'registered international' => [
            static fn() => (new Letter(new Recipient('Mario', '00100', 'Roma', country: 'ITALIEN'), self::document()))->registeredMail(RegisteredMailType::Standard)->toPayload(),
            'E311',
        ];
        yield 'registered premiumadress' => [
            static fn() => self::letter()->registeredMail(RegisteredMailType::Submission)->plugIn(new PremiumAdress())->toPayload(),
            'PremiumAdress is not available for registered mail',
        ];
        yield 'registered automover' => [
            static fn() => self::letter()->registeredMail(RegisteredMailType::Submission)->plugIn(new Automover())->toPayload(),
            'Automover cannot reposition',
        ];
        yield 'custom number too low' => [static fn() => self::letter()->custom(0, 'x'), 'between 1 and 5'];
        yield 'custom number too high' => [static fn() => self::letter()->custom(6, 'x'), 'between 1 and 5'];
        yield 'custom too long' => [static fn() => self::letter()->custom(1, str_repeat('a', 81)), 'custom1 exceeds the maximum length of 80'];
        yield 'cost center too long' => [static fn() => self::letter()->costCenter('123456789'), 'costCenter exceeds the maximum length of 8'];
        yield 'cost center invalid chars' => [static fn() => self::letter()->costCenter('KST-1'), 'letters and digits'];
        yield 'vendor information too long' => [static fn() => self::letter()->vendorSystemInformation(str_repeat('a', 121)), 'vendorSystemInformation exceeds'];
    }

    /**
     * @param callable(): mixed $action
     */
    #[DataProvider('invalid')]
    public function testValidation(callable $action, string $message): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage($message);
        $action();
    }
}
