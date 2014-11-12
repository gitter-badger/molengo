<?php

/**
 * The MIT License (MIT)
 *
 * Copyright (c) 2004-2014 odan
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

namespace Molengo;

/**
 * UnitTest TestSuite
 */
class TestSuite
{

    protected $strTestDir;
    protected $strTemplate;
    protected $strNamespace;
    protected $boolCoverage = false;
    protected $strCoverageDir;

    /**
     * Set test directory with *Test.php files
     *
     * @param string $strTestDir
     * @return void
     */
    public function setTestDir($strTestDir)
    {
        $this->strTestDir = $strTestDir;
    }

    /**
     * Set test namespace
     *
     * @param string $strNamespace
     * @return void
     */
    public function setNamespace($strNamespace)
    {
        $this->strNamespace = $strNamespace;
    }

    /**
     * Set HTML-Template directory for HTML output
     *
     * @param string $strTemplate
     * @return void
     */
    public function setTemplate($strTemplate)
    {
        $this->strTemplate = $strTemplate;
    }

    /**
     * Enable code coverage
     *
     * @param bool $boolCoverage
     * @return void
     */
    public function setCoverage($boolCoverage)
    {
        $this->boolCoverage = $boolCoverage;
    }

    /**
     * Set code coverage output directory
     *
     * @param string $strCoverageDir
     * @return void
     */
    public function setCoverageDir($strCoverageDir)
    {
        $this->strCoverageDir = $strCoverageDir;
    }

    /**
     * Run test suite
     *
     * @return void
     */
    public function run()
    {
        $arrVars = array();

        $suite = new \PHPUnit_Framework_TestSuite();
        $fs = new \Molengo\FileSystem();

        // load all tests
        foreach (glob($this->strTestDir . '/*Test.php') as $tc) {
            $strClass = $this->strNamespace . basename($tc, '.php');
            $suite->addTestSuite(new \ReflectionClass($strClass));
        }

        // coverage
        if ($this->boolCoverage == true) {
            if (file_exists($this->strCoverageDir)) {
                $fs->rrmdir($this->strCoverageDir);
            }
            mkdir($this->strCoverageDir);
            $coverage = new \PHP_CodeCoverage();
            $coverage->start('all');
        }

        // run tests
        /* @var $result PHPUnit_Framework_TestResult */
        $result = $suite->run();

        if ($this->boolCoverage == true) {
            $coverage->stop(true);

            $writer = new \PHP_CodeCoverage_Report_Clover;
            $writer->process($coverage, $this->strCoverageDir . '/clover.xml');

            $writer = new \PHP_CodeCoverage_Report_HTML;
            $writer->process($coverage, $this->strCoverageDir);
        }

        // map testname to test object
        $arrTestSuit = array();
        $topTests = $result->topTestSuite();
        $topTestsTests = $topTests->tests();

        foreach ($topTestsTests as $testTemp) {
            $arrTopTests = $testTemp->tests();
            foreach ($arrTopTests as $tt) {
                $strTestName = get_class($tt) . '::' . $tt->getName();
                $arrTestSuit[$strTestName] = $tt;
            }
        }

        $arrTests = array();

        // errors
        $arrErrors = $result->errors();

        /* @var $error PHPUnit_Framework_TestFailure */
        foreach ($arrErrors as $strTest => $error) {
            $test = $error->failedTest();
            $strName = get_class($error->failedTest()) . '::' . $test->getName();

            $arrTest = array();
            $arrTest['name'] = $strName;
            $arrTest['status'] = false;
            $arrTest['class'] = 'danger';
            $arrTest['message'] = $error->getExceptionAsString();
            $arrTest['time'] = $test->getTime() . ' sec.';
            $arrTest['memory'] = $test->getMemoryStatistic();
            $arrTest['memory_html'] = $this->getMemoryStatistcHml($test->getMemoryStatistic());
            $arrTests[] = $arrTest;
        }

        // failures
        $arrFailures = $result->failures();

        /* @var $failure PHPUnit_Framework_TestFailure */
        foreach ($arrFailures as $strTest => $failure) {
            $test = $failure->failedTest();
            $strName = get_class($failure->failedTest()) . '::' . $test->getName();

            $arrTest = array();
            $arrTest['name'] = $strName;
            $arrTest['status'] = false;
            $arrTest['class'] = 'danger';
            $arrTest['message'] = $failure->getExceptionAsString();
            $arrTest['time'] = $test->getTime() . ' sec.';
            $arrTest['memory'] = $test->getMemoryStatistic();
            $arrTest['memory_html'] = $this->getMemoryStatistcHml($test->getMemoryStatistic());
            $arrTests[] = $arrTest;
        }

        $arrPassed = $result->passed();
        foreach ($arrPassed as $strName => $arrPass) {
            $numTime = 0;
            if (isset($arrTestSuit[$strName])) {
                $test = $arrTestSuit[$strName];
                $numTime = $test->getTime();
            } else {
                throw new Exception('TestSuite not found');
            }

            $arrTest = array();
            $arrTest['name'] = $strName;
            $arrTest['status'] = true;
            $arrTest['class'] = 'success';
            $arrTest['message'] = 'passed';
            $arrTest['time'] = $numTime . ' sec.';
            $arrTest['memory'] = $test->getMemoryStatistic();
            $arrTest['memory_html'] = $this->getMemoryStatistcHml($test->getMemoryStatistic());
            $arrTests[] = $arrTest;
        }

        $arrSkipped = $result->skipped();
        /* @var $skipped PHPUnit_Framework_TestFailure */
        foreach ($arrSkipped as $strTest => $skipped) {
            $test = $failure->failedTest();
            $strName = get_class($skipped->failedTest()) . '::' . $test->getName();

            $arrTest = array();
            $arrTest['name'] = $strName;
            $arrTest['status'] = false;
            $arrTest['class'] = 'warning';
            $arrTest['message'] = $skipped->getExceptionAsString();
            $arrTest['time'] = $test->getTime() . ' sec.';
            $arrTest['memory'] = $test->getMemoryStatistic();
            $arrTest['memory_html'] = $this->getMemoryStatistcHml($test->getMemoryStatistic());
            $arrTests[] = $arrTest;
        }

        // output
        $arrVars['arrTests'] = $arrTests;
        $arrVars['boolCoverage'] = $this->boolCoverage;
        $arrVars['numTimeTotal'] = $result->time();

        $this->renderHtml($arrVars);
    }

    /**
     * Returns memory statistic as HTML
     *
     * @param array $arrMemoryStatistic
     * @return string
     */
    protected function getMemoryStatistcHml($arrMemoryStatistic)
    {
        if (empty($arrMemoryStatistic)) {
            return '';
        }

        $strReturn = '';
        $arrReturn = array();
        $strTpl = '<span class="label label-{label}">{info} {memory_text}</span>';

        // waring at 10mb
        $numWarning = 32 * 1024 * 1024;
        $numDanger = 64 * 1024 * 1024;

        foreach ($arrMemoryStatistic as $arrMem) {
            $strLabel = 'success';

            if ($arrMem['memory'] > $numWarning) {
                $strLabel = 'warning';
            }
            if ($arrMem['memory'] > $numDanger) {
                $strLabel = 'danger';
            }

            $strHtml = i($strTpl, array(
                'label' => $strLabel,
                'info' => gh($arrMem['info']),
                'memory' => gh($arrMem['memory']),
                'memory_text' => gh($arrMem['memory_text'])
            ));
            $arrReturn[] = $strHtml;
        }
        $strReturn = implode("<br>\n", $arrReturn);
        return $strReturn;
    }

    /**
     * Render HTML template with variables
     * @param array $arrVars
     */
    protected function renderHtml($arrVars = array())
    {
        extract($arrVars, EXTR_REFS);
        require $this->strTemplate;
    }

}
