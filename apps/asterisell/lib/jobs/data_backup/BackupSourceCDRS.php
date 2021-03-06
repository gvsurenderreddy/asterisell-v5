<?php

/* $LICENSE 2015:
 *
 * Copyright (C) 2015 Massimo Zaniboni <massimo.zaniboni@asterisell.com>
 *
 * This file is part of Asterisell.
 *
 * Asterisell is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * Asterisell is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Asterisell. If not, see <http://www.gnu.org/licenses/>.
 * $
 */

sfLoader::loadHelpers(array('I18N', 'Debug', 'Date', 'Asterisell'));

/**
 * Backup SourceCDRS changed in a day.
 */
class BackupSourceCDRS extends DailyBackupJob
{

    function getAtSpecificTime()
    {
        return '00:30:00';
    }

    function getHoursExecutionTimeFrame()
    {
        return 24;
    }

    function getLogicalType()
    {
        return 'source-cdrs';
    }

    public function processChangedDay($fromDate, $toDate, $isServiceCDR, PropelPDO $conn)
    {

        $year = date('Y', $fromDate);
        $month = date('m', $fromDate);
        $day = date('d', $fromDate);

        $fileName = $this->getLogicalType() . '-' . "$year-$month-$day.csv";
        $tmpFileName = normalizeFileNamePath($this->getMySQLAccessibleTmpDirectory() . '/' . $fileName);
        @unlink($tmpFileName);

        // NOTE: this file can be recognized from MySQL using LOAD FUNCTION, but not from Haskell, because the escape character is `\` instead of  `"`
        $query = <<<'NOWDOC'
        SELECT ar_cdr_provider_id, ar_physical_format_id, calldate, is_imported_service_cdr, content
NOWDOC;
        $query .= " INTO OUTFILE '$tmpFileName' ";

        $query .= <<<'NOWDOC'
        CHARACTER SET 'utf8'
        FIELDS TERMINATED BY ','
        OPTIONALLY ENCLOSED BY '"'
        ESCAPED BY '\\'
        LINES TERMINATED BY '\r\n'
        FROM ar_source_cdr
        WHERE calldate >= ? AND calldate < ?
NOWDOC;

        $stmt = $conn->prepare($query);

        $isOk = $stmt->execute(array(
            fromUnixTimestampToMySQLTimestamp($fromDate),
            fromUnixTimestampToMySQLTimestamp($toDate)));

        if ($isOk === FALSE) {
            throw $this->createError(
                ArProblemType::TYPE_ERROR,
                ArProblemDomain::APPLICATION,
                'error in query ' . $query,
                "Error in query \"" . $query . "\"",
                "CDRs will be not backuped.",
                "This is an error in the code. Contact the assistance."
            );
        }
        $stmt->closeCursor();

        $exportDirectory = $this->createAndGetAbsoluteArchiveDirectory($fromDate);
        $dstFile = normalizeFileNamePath($exportDirectory . '/' . $fileName);
        @unlink($dstFile);

        $isOk = rename($tmpFileName, $dstFile);
        if ($isOk === FALSE) {
            throw $this->createError(
                ArProblemType::TYPE_ERROR,
                ArProblemDomain::CONFIGURATIONS,
                'unable to backup CDR file ' . $dstFile,
                "Unable to move the file $tmpFileName to $dstFile, during backup of CDRs.",
                "CDRs can not be backup.",
                "Check the directory have the correct access permissions. If the problem persists contact the assistance."
            );
        }
    }

    /**
     * @param string $sourceDir
     * @param string $sourceFileName
     * @throws ArProblemException
     */
    protected function restoreFile($sourceDir, $sourceFileName)
    {
        $sourceFile = normalizeFileNamePath($sourceDir . '/' . $sourceFileName);
        $tmpFile = normalizeFileNamePath(ImportDataFiles::getMySQLAccessibleTmpDirectory(get_class($this)) . '/restore_source_cdrs.csv');
        @unlink($tmpFile);

        if (filesize($sourceFile) > 0) {
            $isOk = copy($sourceFile, $tmpFile);
            if ($isOk === FALSE) {
                throw $this->createBackupError(
                    'unable to copy file into work directory ',
                    "Unable to copy file \"$sourceFile\" into \"$tmpFile\""
                );
            }

            $conn = Propel::getConnection();
            $conn->beginTransaction();
            try {

                $cmd = "LOAD DATA INFILE '$tmpFile' ";
                $cmd .= <<<'HEREDOC'
        INTO TABLE ar_source_cdr
        CHARACTER SET 'utf8'
        FIELDS TERMINATED BY ','
        OPTIONALLY ENCLOSED BY '"'
        ESCAPED BY '\\'
        LINES TERMINATED BY '\r\n' STARTING BY ''
        (ar_cdr_provider_id, ar_physical_format_id, calldate, is_imported_service_cdr, content) SET id = NULL;
        ';
HEREDOC;
                $isOk = $conn->exec($cmd);
                if ($isOk === FALSE) {
                    throw $this->createBackupError(
                        'unable to insert file ' . $tmpFile,
                        "Unable to insert the content of file \"$tmpFile\" into database."
                    );
                }

                @unlink($tmpFile);

                $this->commitTransactionOrSignalProblem($conn);
            } catch (ArProblemException $e) {
                $this->maybeRollbackTransaction($conn);
                throw($e);
            } catch (Exception $e) {
                $this->maybeRollbackTransaction($conn);
                throw($this->createBackupError(
                    'unable to commit transaction ',
                    'Unable to commit transaction ' . $cmd
                ));
            }
        }
    }

    public function restoreFromBackup()
    {
        $dir1 = normalizeFileNamePath(getAsterisellCompleteRootDirectory() . '/' . self::ARCHIVE_DIRECTORY . '/' . $this->getLogicalType());
        $dh1 = opendir($dir1);

        try {
            if ($dh1 !== FALSE) {
                // scan years
                while (($file1 = readdir($dh1)) !== false) {
                    if ($file1 != "." && $file1 != "..") {
                        $dir2 = normalizeFileNamePath($dir1 . '/' . $file1);

                        $dh2 = opendir($dir2);
                        if ($dh2 !== FALSE) {
                            // scan months
                            while (($file2 = readdir($dh2)) !== false) {
                                if ($file2 != "." && $file2 != "..") {

                                    $dir3 = normalizeFileNamePath($dir2 . '/' . $file2);
                                    $dh3 = opendir($dir3);
                                    if ($dh3 !== FALSE) {
                                        // scan files
                                        while (($file3 = readdir($dh3)) !== false) {
                                            if ($file3 != "." && $file3 != "..") {
                                                $this->restoreFile($dir3, $file3);
                                            }
                                        }
                                    } else {
                                        return "Unable to open $dir3";
                                    }
                                }
                            }
                            closedir($dh2);
                        } else {
                            return "Unable to open $dir2";
                        }
                    }
                }
                closedir($dh1);

            } else {
                return "Unable to open $dir1";
            }
        } catch (ArProblemException $e) {
            return $e->getMessage();
        }

        return null;
    }

}
