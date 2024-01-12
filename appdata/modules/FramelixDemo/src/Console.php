<?php

namespace Framelix\FramelixDemo;

use Framelix\Framelix\Date;
use Framelix\Framelix\Network\UploadedFile;
use Framelix\Framelix\Storable\Storable;
use Framelix\Framelix\Storable\StorableFile;
use Framelix\Framelix\Storable\User;
use Framelix\Framelix\Utils\Mutex;
use Framelix\Framelix\Utils\RandomGenerator;
use Framelix\FramelixDemo\Storable\Fixation;
use Framelix\FramelixDemo\Storable\Income;
use Framelix\FramelixDemo\Storable\Invoice;
use Framelix\FramelixDemo\Storable\InvoicePosition;
use Framelix\FramelixDemo\Storable\Outgoing;
use Framelix\FramelixDemo\Storable\SystemValue\IncomeCategory;
use Framelix\FramelixDemo\Storable\SystemValue\InvoiceCreator;
use Framelix\FramelixDemo\Storable\SystemValue\OutgoingCategory;
use Framelix\FramelixDemo\Storable\SystemValue\SummaryKey;

use function shuffle;
use function substr;
use function ucfirst;

class Console extends \Framelix\Framelix\Console
{
    public static ?true $cleanupMode = null;

    private static array $randomWords = [
        'lilies',
        'willful',
        'cauldron',
        'indiscretions',
        'prosthetic',
        'beta',
        'edited',
        'fascism',
        'advice',
        'ruff',
        'mobile',
        'wade',
        'sew',
        'unacceptable',
        'psychologist',
        'fighting',
        'wont',
        'commode',
        'nonstop',
        'vendor',
        'listener',
        'fable',
        'reserving',
        'reluctantly',
        'jammies',
        'navigator',
        'snoring',
        'congratulate',
        'academics',
        'faucets',
        'intruding',
        'shanks',
        'aid',
        'buckling',
        'shelling',
        'fifties',
        'eastbound',
        'blitz',
        'bossy',
        'penne',
        'pestering',
        'vasectomy',
        'sacks',
        'bleach',
        'ose',
        'costs',
        'obsessive',
        'mastermind',
        'coveralls',
        'recaptured'
    ];

    public static function appWarmup(): int
    {
        self::cleanupDemoData();
        return 0;
    }

    /**
     * Delete all demo data and recreate some fresh demo data
     * @return int Status Code, 0 = success
     */
    public static function cleanupDemoData(): int
    {
        if (!\Framelix\Framelix\Config::doesUserConfigFileExist()) {
            return 0;
        }
        self::$cleanupMode = true;

        Mutex::create(Cron::CLEANUP_MUTEX_NAME);
        Storable::deleteMultiple(Storable::getByCondition());

        $user = User::getByEmail('test@test.local', true);
        if (!$user) {
            // create the test user if not yet exist
            $user = new  User();
            $user->email = "test@test.local";
            $pw = RandomGenerator::getRandomString(5, 10);
            $user->setPassword($pw);
            $user->flagLocked = false;
            $user->store();
            $user->addRole('admin');
        }

        // set user to be used for next store methods
        User::setCurrentUser($user);

        $incomeCategories = [];
        $values = [
            "Softwaredevelopment",
            "Licenses",
            self::getRandWords(3),
            self::getRandWords(2),
            "Other"
        ];
        foreach ($values as $sort => $value) {
            $category = new IncomeCategory();
            $category->name = $value;
            $category->sort = $sort;
            $category->flagActive = true;
            $category->store();
            $incomeCategories[$category->id] = $category;
        }

        $values = [
            "Office",
            "Domains",
            "Meat and Greet",
            "Mobile Phone",
            "Internet",
            "Hardware",
            "Licenses",
            "Internal costs",
            self::getRandWords(3),
            self::getRandWords(2),
            "Other"
        ];
        $outgoingCategories = [];
        foreach ($values as $sort => $value) {
            $category = new OutgoingCategory();
            $category->name = $value;
            $category->sort = $sort;
            $category->flagActive = true;
            $category->operationalSharePercent = rand(0, 1) ? 100 : 60;
            $category->store();
            $outgoingCategories[$category->id] = $category;
        }

        $summaryKey = new SummaryKey();
        shuffle($incomeCategories);
        $summaryKey->name = "Come in";
        $summaryKey->category = reset($incomeCategories);
        $summaryKey->key = "1920";
        $summaryKey->incomeCategory = SummaryKey::SUM_CATEGORY_PLUS;
        $summaryKey->outgoingCategory = SummaryKey::SUM_CATEGORY_MINUS;
        $summaryKey->sort = 0;
        $summaryKey->flagActive = true;
        $summaryKey->store();

        $summaryKey = new SummaryKey();
        shuffle($outgoingCategories);
        $summaryKey->name = "And out";
        $summaryKey->category = reset($outgoingCategories);
        $summaryKey->key = "6666";
        $summaryKey->incomeCategory = SummaryKey::SUM_CATEGORY_PLUS;
        $summaryKey->outgoingCategory = SummaryKey::SUM_CATEGORY_MINUS;
        $summaryKey->sort = 0;
        $summaryKey->flagActive = true;
        $summaryKey->store();

        $invoiceHeader = new StorableFile();
        $invoiceHeader->store(
            file: UploadedFile::createFromFile(__DIR__ . "/../misc/invoice-header.png"),
            copy: true
        );

        $invoiceCreator = new InvoiceCreator();
        $invoiceCreator->invoiceHeader = $invoiceHeader;
        $invoiceCreator->vatId = "ATU6666666";
        $invoiceCreator->address = "Heavensplace 1\n6666 Port Hell\nDownBellow";
        $invoiceCreator->invoiceTextAfterPositions = "Thank you for testing this demo.";
        $invoiceCreator->accountName = "Devil0";
        $invoiceCreator->iban = "AT00660066006600660066";
        $invoiceCreator->bic = "ATDATATDAT";
        $invoiceCreator->sort = 0;
        $invoiceCreator->flagActive = true;
        $invoiceCreator->store();

        $incomes = [];
        for ($i = 0; $i <= 500; $i++) {
            $income = new Income();
            $income->date = Date::create("now - $i days");
            if (rand(0, 1)) {
                $income->comment = self::getRandWords(rand(1, 6));
            }
            shuffle($incomeCategories);
            $income->incomeCategory = reset($incomeCategories);
            $income->net = (float)RandomGenerator::getRandomInt(1, 9999);
            if (rand(0, 1)) {
                $income->net += RandomGenerator::getRandomInt(1, 100) / 100;
            }
            $income->store();
            if (rand(0, 10) === 10) {
                self::assignRandomAttachmentFile($income);
            }
            $incomes[$income->id] = $income;
        }

        for ($i = 0; $i <= 500; $i++) {
            $outgoing = new Outgoing();
            $outgoing->date = Date::create("now - $i days");
            if (rand(0, 1)) {
                $outgoing->comment = self::getRandWords(rand(1, 6));
            }
            shuffle($outgoingCategories);
            $outgoing->outgoingCategory = reset($outgoingCategories);
            $outgoing->net = (float)RandomGenerator::getRandomInt(1, 9999);
            if (rand(0, 1)) {
                $outgoing->net += RandomGenerator::getRandomInt(1, 100) / 100;
            }
            $outgoing->store();
            if (rand(0, 10) === 10) {
                self::assignRandomAttachmentFile($outgoing);
            }
        }

        for ($i = 0; $i <= 50; $i++) {
            $invoice = new Invoice();
            $invoice->category = Invoice::CATEGORY_INVOICE;
            $invoice->date = Date::create("now - " . ($i * 10) . " days");
            if (rand(0, 1)) {
                $invoice->datePaid = Date::create("now - " . ($i * rand(1, 10)) . " days");
            }
            $net = RandomGenerator::getRandomInt(10, 9999);
            if (rand(0, 1)) {
                shuffle($incomes);
                $invoice->income = reset($incomes);
                $net = $invoice->income->net;
            }
            $invoice->creator = $invoiceCreator;
            $invoice->receiver = self::getRandWords(2);
            if (rand(0, 1)) {
                $invoice->textBeforePosition = self::getRandWords(10);
            }
            if (rand(0, 1)) {
                $invoice->textAfterPosition = self::getRandWords(10);
            }
            $invoice->store();
            $sort = 0;
            while ($net > 0) {
                $totalPos = RandomGenerator::getRandomInt((int)floor(min($net, 2000)), (int)floor(min($net, 5000)));
                if ($totalPos > $net) {
                    $totalPos = $net;
                }
                if ($net <= 2) {
                    $totalPos = $net;
                }
                $invoicePos = new InvoicePosition();
                $invoicePos->invoice = $invoice;
                $invoicePos->count = RandomGenerator::getRandomInt(1, 5);
                $invoicePos->netSingle = (float)($totalPos / $invoicePos->count);
                $invoicePos->comment = self::getRandWords(5);
                $invoicePos->sort = $sort++;
                $invoicePos->store();
                $net -= $invoicePos->netSingle * $invoicePos->count;
            }
        }

        do {
            $range = Fixation::getNextFixationDateRange();
            $fixation = Fixation::createFixationForRange($range[0], $range[1]);
            $year = $fixation->dateTo->dateTime->getYear();
            if ($year === (int)date("Y")) {
                $fixation->delete();
            }
        } while ($year !== (int)date("Y"));

        return 0;
    }

    private static function getRandWords(int $nr): string
    {
        $str = "";
        for ($i = 1; $i <= $nr; $i++) {
            shuffle(self::$randomWords);
            $str .= ucfirst(self::$randomWords[0]) . " ";
        }
        return substr($str, 0, -1);
    }

    private static function assignRandomAttachmentFile(Storable $assignedStorable): void
    {
        $attachment = new StorableFile();
        $attachment->assignedStorable = $assignedStorable;
        $attachment->store(
            file: UploadedFile::createFromFile(
            __DIR__ . "/../misc/" . (rand(
                0,
                1
            ) ? 'you-got-a-framelix-pdf.pdf' : 'you-got-a-framelix-spreadsheet.xlsx')
        ),
            copy: true
        );
    }
}