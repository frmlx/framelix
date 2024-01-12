<?php

namespace Framelix\FramelixDemo\Storable;

use Framelix\Framelix\Date;
use Framelix\Framelix\Db\Sql;
use Framelix\Framelix\Storable\StorableExtended;
use Framelix\Framelix\Storable\StorableFile;
use Framelix\FramelixDemo\Console;
use Framelix\FramelixDemo\View\Reports;

/**
 * Fixation
 * @property StorableFile|null $attachment
 * @property Date $dateFrom
 * @property Date $dateTo
 */
class Fixation extends StorableExtended
{

    public static function createFixationForRange(Date $dateFrom, Date $dateTo): Fixation
    {
        $fixation = new Fixation();
        $fixation->dateFrom = $dateFrom;
        $fixation->dateTo = $dateTo;

        // just testing generation, if some error happens, it will stop here and throw an exception
        Reports::getReportSheet($fixation->dateFrom, $fixation->dateTo);

        $fixation->store();

        $outgoings = Outgoing::getByCondition(
            'fixation IS NULL && date BETWEEN {0} AND {1}',
            [$fixation->dateFrom, $fixation->dateTo],
            ['+date', '+id']
        );
        Sql::get()->update(Outgoing::class, ['nr' => null], 'fixation IS NULL');
        $lastOutgoing = Outgoing::getByConditionOne('fixation IS NOT NULL', null, ['-nr']);
        $lastNr = $lastOutgoing->nr ?? 0;
        foreach ($outgoings as $outgoing) {
            $lastNr++;
            $outgoing->nr = $lastNr;
            $outgoing->fixation = $fixation;
            $outgoing->preserveUpdateUserAndTime();
            $outgoing->store(true);
        }

        $incomes = Income::getByCondition(
            'fixation IS NULL && date BETWEEN {0} AND {1}',
            [$fixation->dateFrom, $fixation->dateTo],
            ['+date', '+id']
        );
        Sql::get()->update(Income::class, ['nr' => null], 'fixation IS NULL');
        $lastIncome = Income::getByConditionOne('fixation IS NOT NULL', null, ['-nr']);
        $lastNr = $lastIncome->nr ?? 0;
        foreach ($incomes as $income) {
            $lastNr++;
            $income->nr = $lastNr;
            $income->fixation = $fixation;
            $income->preserveUpdateUserAndTime();
            $income->store(true);
        }

        $invoices = Invoice::getByCondition(
            'fixation IS NULL && date BETWEEN {0} AND {1}',
            [$fixation->dateFrom, $fixation->dateTo]
        );
        foreach ($invoices as $invoice) {
            $invoice->fixation = $fixation;
            $invoice->preserveUpdateUserAndTime();
            $invoice->store(true);
        }

        $reportSheet = Reports::getReportSheet($fixation->dateFrom, $fixation->dateTo);
        $tmpFile = tempnam(sys_get_temp_dir(), 'framelixDemo-fixation') . ".xlsx";
        $reportSheet->save($tmpFile);

        $attachment = new StorableFile();
        $attachment->assignedStorable = $fixation;
        $attachment->filename = "fixation-" . $fixation->dateFrom->getRawTextString(
            ) . "-" . $fixation->dateTo->getRawTextString() . ".xlsx";
        $attachment->store(false, file_get_contents($tmpFile));
        unlink($tmpFile);

        $fixation->attachment = $attachment;
        $fixation->store();
        return $fixation;
    }

    /**
     * Get min start and max end date for next fixation
     * @return Date[]
     */
    public static function getNextFixationDateRange(): array
    {
        $db = Sql::get();
        $firstUnfixedIncome = $db->fetchOne(
            "SELECT `date` FROM `" . Income::class . "` WHERE fixation IS NULL ORDER BY `date` ASC LIMIT 1"
        );
        $lastUnfixedIncome = $db->fetchOne(
            "SELECT `date` FROM `" . Income::class . "` WHERE fixation IS NULL ORDER BY `date` DESC LIMIT 1"
        );
        $firstUnfixedOutgoing = $db->fetchOne(
            "SELECT `date` FROM `" . Outgoing::class . "` WHERE fixation IS NULL ORDER BY `date` ASC LIMIT 1"
        );
        $lastUnfixedOutgoing = $db->fetchOne(
            "SELECT `date` FROM `" . Outgoing::class . "` WHERE fixation IS NULL ORDER BY `date` DESC LIMIT 1"
        );
        $lastFixation = Fixation::getByConditionOne(sort: ['-dateTo']);
        if ($lastFixation) {
            $startDate = $lastFixation->dateTo->clone();
            $startDate->dateTime->modify("+ 1 day");
        } else {
            $startDate = Date::min(
                $firstUnfixedIncome,
                $firstUnfixedOutgoing,
                "now"
            );
            if (!$startDate) {
                $startDate = Date::create('now');
            }
            $startDate->dateTime->setDayOfMonth(1);
        }
        $lastDate = Date::max(
            $lastUnfixedIncome,
            $lastUnfixedOutgoing,
            'now',
            $startDate
        );
        $lastDate = Date::min(
            $lastDate,
            $startDate->clone()->dateTime->setDate($startDate->dateTime->getYear(), 12, 31)
        );
        return [$startDate, $lastDate];
    }

    public function isDeletable(): bool
    {
        if (Console::$cleanupMode) {
            return true;
        }
        $nextFixation = self::getByConditionOne('dateFrom > {0}', [$this->dateFrom]);
        if ($nextFixation) {
            return false;
        }
        return true;
    }

    public function delete(bool $force = false): void
    {
        $storables = [Income::class, Outgoing::class, Invoice::class];
        foreach ($storables as $storableClass) {
            $this->getDb()->update($storableClass, ['fixation' => null], 'fixation = ' . $this);
        }
        $this->attachment?->delete($force);
        parent::delete($force);
    }


}