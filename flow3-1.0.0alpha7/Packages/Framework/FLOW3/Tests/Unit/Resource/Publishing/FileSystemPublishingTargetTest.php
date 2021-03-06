<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Resource\Publishing;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

require_once('vfs/vfsStream.php');

/**
 * Testcase for the File System Publishing Target
 *
 * @version $Id: FileSystemPublishingTargetTest.php 3548 2009-12-21 16:21:30Z robert $
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class FileSystemPublishingTargetTest extends \F3\Testing\BaseTestCase {

	/**
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setUp() {
		\vfsStreamWrapper::register();
		\vfsStreamWrapper::setRoot(new \vfsStreamDirectory('Foo'));
	}

	/**
	 * Checks if the package autoloader loads classes from subdirectories.
	 *
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function initalizeObjectCreatesDirectoriesAndDetectsTheResourcesBaseUri() {
		$publishingTarget = $this->getMock($this->buildAccessibleProxy('\F3\FLOW3\Resource\Publishing\FileSystemPublishingTarget'), array('detectResourcesBaseUri'));
		$publishingTarget->expects($this->once())->method('detectResourcesBaseUri');

		$publishingTarget->_set('resourcesPublishingPath', 'vfs://Foo/Web/_Resources/');
		$publishingTarget->initializeObject();

		$this->assertFileExists('vfs://Foo/Web/_Resources');
		$this->assertFileExists('vfs://Foo/Web/_Resources/Persistent');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function publishStaticResourcesReturnsFalseIfTheGivenSourceDirectoryDoesntExist() {
		$publishingTarget = $this->getMock($this->buildAccessibleProxy('\F3\FLOW3\Resource\Publishing\FileSystemPublishingTarget'), array('dummy'));
		$this->assertFalse($publishingTarget->publishStaticResources('vfs://Foo/Bar', 'x'));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function publishStaticResourcesMirrorsRecursivelyAllFilesExceptPHPFoundInTheSpecifiedDirectory() {
		mkdir('vfs://Foo/Sources');
		mkdir('vfs://Foo/Sources/SubDirectory');
		mkdir('vfs://Foo/Sources/SubDirectory/SubSubDirectory');

		file_put_contents('vfs://Foo/Sources/file1.txt', 1);
		file_put_contents('vfs://Foo/Sources/file2.txt', 1);
		file_put_contents('vfs://Foo/Sources/SubDirectory/file2.txt', 1);
		file_put_contents('vfs://Foo/Sources/SubDirectory/SubSubDirectory/file3.txt', 1);
		file_put_contents('vfs://Foo/Sources/SubDirectory/SubSubDirectory/file4.php', 1);
		file_put_contents('vfs://Foo/Sources/SubDirectory/SubSubDirectory/file5.jpg', 1);

		mkdir('vfs://Foo/Web');
		mkdir('vfs://Foo/Web/_Resources');


		$publishingTarget = $this->getMock($this->buildAccessibleProxy('\F3\FLOW3\Resource\Publishing\FileSystemPublishingTarget'), array('mirrorFile'));
		$publishingTarget->_set('resourcesPublishingPath', 'vfs://Foo/Web/_Resources/');

		$publishingTarget->expects($this->at(0))->method('mirrorFile')->with('vfs://Foo/Sources/SubDirectory/SubSubDirectory/file3.txt', 'vfs://Foo/Web/_Resources/Static/Bar/SubDirectory/SubSubDirectory/file3.txt');
		$publishingTarget->expects($this->at(1))->method('mirrorFile')->with('vfs://Foo/Sources/SubDirectory/SubSubDirectory/file5.jpg', 'vfs://Foo/Web/_Resources/Static/Bar/SubDirectory/SubSubDirectory/file5.jpg');
		$publishingTarget->expects($this->at(2))->method('mirrorFile')->with('vfs://Foo/Sources/SubDirectory/file2.txt', 'vfs://Foo/Web/_Resources/Static/Bar/SubDirectory/file2.txt');
		$publishingTarget->expects($this->at(3))->method('mirrorFile')->with('vfs://Foo/Sources/file1.txt', 'vfs://Foo/Web/_Resources/Static/Bar/file1.txt');
		$publishingTarget->expects($this->at(4))->method('mirrorFile')->with('vfs://Foo/Sources/file2.txt', 'vfs://Foo/Web/_Resources/Static/Bar/file2.txt');

		$result = $publishingTarget->publishStaticResources('vfs://Foo/Sources', 'Bar');
		$this->assertTrue($result);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function publishStaticResourcesDoesNotMirrorAFileIfItAlreadyExistsAndTheModificationTimeIsEqualOrNewer() {
		mkdir('vfs://Foo/Sources');

		file_put_contents('vfs://Foo/Sources/file1.txt', 1);
		file_put_contents('vfs://Foo/Sources/file2.txt', 1);
		file_put_contents('vfs://Foo/Sources/file3.txt', 1);

		\F3\FLOW3\Utility\Files::createDirectoryRecursively('vfs://Foo/Web/_Resources/Static/Bar');

		file_put_contents('vfs://Foo/Web/_Resources/Static/Bar/file2.txt', 1);
		\vfsStreamWrapper::getRoot()->getChild('Web/_Resources/Static/Bar/file2.txt')->setFilemtime(time() - 5);

		file_put_contents('vfs://Foo/Web/_Resources/Static/Bar/file3.txt', 1);

		$mirrorFileCallback = function($sourcePathAndFilename, $targetPathAndFilename) {
			if ($sourcePathAndFilename === 'vfs://Foo/Sources/file3.txt') {
				throw new \Exception('file3.txt should not have been mirrored.');
			}
		};

		$publishingTarget = $this->getMock($this->buildAccessibleProxy('\F3\FLOW3\Resource\Publishing\FileSystemPublishingTarget'), array('mirrorFile'));
		$publishingTarget->_set('resourcesPublishingPath', 'vfs://Foo/Web/_Resources/');

		$publishingTarget->expects($this->exactly(2))->method('mirrorFile')->will($this->returnCallback($mirrorFileCallback));

		$result = $publishingTarget->publishStaticResources('vfs://Foo/Sources', 'Bar');

		$this->assertTrue($result);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function publishPersistentResourceMirrorsTheGivenResource() {
		$mockResource = $this->getMock('F3\FLOW3\Resource\Resource', array(), array(), '', FALSE);
		$mockResource->expects($this->atLeastOnce())->method('getHash')->will($this->returnValue('ac9b6187f4c55b461d69e22a57925ff61ee89cb2'));
		$mockResource->expects($this->atLeastOnce())->method('getFileExtension')->will($this->returnValue('jpg'));

		$publishingTarget = $this->getMock($this->buildAccessibleProxy('\F3\FLOW3\Resource\Publishing\FileSystemPublishingTarget'), array('rewriteTitleForUri', 'getPersistentResourceSourcePathAndFilename', 'mirrorFile'));
		$publishingTarget->expects($this->once())->method('getPersistentResourceSourcePathAndFilename')->with($mockResource)->will($this->returnValue('source.jpg'));
		$publishingTarget->expects($this->once())->method('mirrorFile')->with('source.jpg', 'vfs://Foo/Web/Persistent/ac9b6187f4c55b461d69e22a57925ff61ee89cb2.jpg');

		$publishingTarget->_set('resourcesPublishingPath', 'vfs://Foo/Web/');
		$publishingTarget->_set('resourcesBaseUri', 'http://Foo/_Resources/');

		$this->assertSame('http://Foo/_Resources/Persistent/ac9b6187f4c55b461d69e22a57925ff61ee89cb2.jpg', $publishingTarget->publishPersistentResource($mockResource));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function publishPersistentResourceMirrorsTheGivenSourceFileDoesntExist() {
		$mockResource = $this->getMock('F3\FLOW3\Resource\Resource', array(), array(), '', FALSE);
		$mockResource->expects($this->atLeastOnce())->method('getHash')->will($this->returnValue('ac9b6187f4c55b461d69e22a57925ff61ee89cb2'));
		$mockResource->expects($this->atLeastOnce())->method('getFileExtension')->will($this->returnValue('jpg'));

		$publishingTarget = $this->getMock($this->buildAccessibleProxy('\F3\FLOW3\Resource\Publishing\FileSystemPublishingTarget'), array('rewriteTitleForUri', 'getPersistentResourceSourcePathAndFilename'));
		$publishingTarget->expects($this->once())->method('getPersistentResourceSourcePathAndFilename')->with($mockResource)->will($this->returnValue(FALSE));

		$publishingTarget->_set('resourcesPublishingPath', 'vfs://Foo/Web/');

		$this->assertFalse($publishingTarget->publishPersistentResource($mockResource));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function publishPersistentResourceDoesNotMirrorTheResourceIfItAlreadyExistsInThePublishingDirectory() {
		mkdir('vfs://Foo/Web');
		mkdir('vfs://Foo/Web/Persistent');
		file_put_contents('vfs://Foo/Web/Persistent/ac9b6187f4c55b461d69e22a57925ff61ee89cb2.jpg', 'some data');

		$mockResource = $this->getMock('F3\FLOW3\Resource\Resource', array(), array(), '', FALSE);
		$mockResource->expects($this->atLeastOnce())->method('getHash')->will($this->returnValue('ac9b6187f4c55b461d69e22a57925ff61ee89cb2'));
		$mockResource->expects($this->atLeastOnce())->method('getFileExtension')->will($this->returnValue('jpg'));

		$publishingTarget = $this->getMock($this->buildAccessibleProxy('\F3\FLOW3\Resource\Publishing\FileSystemPublishingTarget'), array('rewriteTitleForUri', 'getPersistentResourceSourcePathAndFilename'));

		$publishingTarget->_set('resourcesPublishingPath', 'vfs://Foo/Web/');
		$publishingTarget->_set('resourcesBaseUri', 'http://host/dir/');

		$publishingTarget->publishPersistentResource($mockResource);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getStaticResourcesWebBaseUriReturnsJustThat() {
		$publishingTarget = $this->getMock($this->buildAccessibleProxy('\F3\FLOW3\Resource\Publishing\FileSystemPublishingTarget'), array('dummy'));
		$publishingTarget->_set('resourcesBaseUri', 'http://host/dir/');

		$this->assertSame('http://host/dir/Static/', $publishingTarget->getStaticResourcesWebBaseUri());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getPersistentResourceWebUriJustCallsPublishPersistentResource() {
		$mockResource = $this->getMock('F3\FLOW3\Resource\Resource', array(), array(), '', FALSE);
		$title = 'Some Title';

		$publishingTarget = $this->getMock($this->buildAccessibleProxy('\F3\FLOW3\Resource\Publishing\FileSystemPublishingTarget'), array('publishPersistentResource'));
		$publishingTarget->expects($this->once())->method('publishPersistentResource')->with($mockResource, $title)->will($this->returnValue('http://result'));

		$this->assertSame('http://result', $publishingTarget->getPersistentResourceWebUri($mockResource, $title));
	}

	/**
	 * Because mirrorFile() uses touch() we can't use vfs to mock the file system.
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function mirrorFileCopiesTheGivenFileIfTheSettingSaysSo() {
		$sourcePathAndFilename = FLOW3_PATH_DATA . 'Temporary/' . uniqid();
		$targetPathAndFilename = FLOW3_PATH_DATA . 'Temporary/' . uniqid();
		
		file_put_contents($sourcePathAndFilename, 'some data');
		touch($sourcePathAndFilename, time() - 5);

		$settings = array('resource' => array('publishing' => array('fileSystem' => array('mirrorMode' => 'copy'))));

		$publishingTarget = $this->getMock($this->buildAccessibleProxy('\F3\FLOW3\Resource\Publishing\FileSystemPublishingTarget'), array('dummy'));
		$publishingTarget->injectSettings($settings);

		$publishingTarget->_call('mirrorFile', $sourcePathAndFilename, $targetPathAndFilename, TRUE);
		$this->assertFileEquals($sourcePathAndFilename, $targetPathAndFilename);

		clearstatcache();
		$this->assertSame(filemtime($sourcePathAndFilename), filemtime($targetPathAndFilename));

		unlink($sourcePathAndFilename);
		unlink($targetPathAndFilename);
	}

	/**
	 * Because mirrorFile() uses touch() we can't use vfs to mock the file system.
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function mirrorFileSymLinksTheGivenFileIfTheSettingSaysSo() {
		$sourcePathAndFilename = FLOW3_PATH_DATA . 'Temporary/' . uniqid();
		$targetPathAndFilename = FLOW3_PATH_DATA . 'Temporary/' . uniqid();

		file_put_contents($sourcePathAndFilename, 'some data');
		touch($sourcePathAndFilename, time() - 5);

		$settings = array('resource' => array('publishing' => array('fileSystem' => array('mirrorMode' => 'link'))));

		$publishingTarget = $this->getMock($this->buildAccessibleProxy('\F3\FLOW3\Resource\Publishing\FileSystemPublishingTarget'), array('dummy'));
		$publishingTarget->_set('settings', $settings);

		$publishingTarget->_call('mirrorFile', $sourcePathAndFilename, $targetPathAndFilename, TRUE);
		$this->assertFileEquals($sourcePathAndFilename, $targetPathAndFilename);
		$this->assertTrue(is_link($targetPathAndFilename));
		
		unlink($sourcePathAndFilename);
		unlink($targetPathAndFilename);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function detectResourcesBaseUriDetectsUriWithSubDirectoryCorrectly() {
		$mockEnvironment = $this->getMock('F3\FLOW3\Utility\Environment', array(), array(), '', FALSE);
		$mockEnvironment->expects($this->once())->method('getRequestUri')->will($this->returnValue(new \F3\FLOW3\Property\DataType\Uri('http://www.server.com/path1/path2/')));

		$expectedBaseUri = 'http://www.server.com/_Resources/';

		$publishingTarget = $this->getMock($this->buildAccessibleProxy('\F3\FLOW3\Resource\Publishing\FileSystemPublishingTarget'), array('dummy'));
		$publishingTarget->_set('resourcesPublishingPath', FLOW3_PATH_WEB . '_Resources/');
		$publishingTarget->injectEnvironment($mockEnvironment);

		$publishingTarget->_call('detectResourcesBaseUri');

		$actualBaseUri = $publishingTarget->_get('resourcesBaseUri');
		$this->assertSame($expectedBaseUri, $actualBaseUri);
	}
}
?>