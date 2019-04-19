<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 19.08.2018
 * Time: 18:19
 */

namespace RozaVerta\CmfCore\Controllers;

use Doctrine\DBAL\DBALException;
use RozaVerta\CmfCore\Route\Controller;
use RozaVerta\CmfCore\Schemes\Modules_SchemeDesigner;
use RozaVerta\CmfCore\Support\Prop;

class Welcome extends Controller
{
	protected $page;

	protected $menu = [
		[
			"id"    => 1,
			"name"  => "index",
			"title" => "Welcome",
			"links" => [
				[
					"link"  => "https://rozaverta.com/cmf",
					"title" => "https://rozaverta.com"
				],
				[
					"link"  => "https://github.com/rozaverta/cmf-core",
					"title" => "GitHub"
				]
			]
		],
		[
			"id"    => 2,
			"name"  => "system",
			"title" => "System"
		],
		[
			"id"    => 3,
			"name"  => "license",
			"title" => "License"
		]
	];

	public function ready(): bool
	{
		$url = $this->app->url;

		$this->id  = 404;
		$page_name = $url->count() === 0 ? "index" : ($url->count() === 1 && $url->getDirLength() === 0 ? $url->getSegment(0) : "");

		foreach($this->menu as & $page)
		{
			$page["link"] = $url->makeUrl($page["name"], [], true, true);
			$page["active"] = $page["name"] === $page_name;
			if($page["active"])
			{
				$this->id = $page["id"];
				$this->page = $page;
			}
		}

		if( $this->id === 404 )
		{
			$this->page = [
				"id"        => $this->id,
				"name"      => "404",
				"link"      => $url->getUrl(),
				"title"     => "Page not found",
				"active"    => true
			];

			$this->menu[] = $this->page;
		}

		return true;
	}

	public function complete()
	{
		$this->pageData["pageTitle"] = $this->page["title"];
		$this->pageData["menu"] = $this->menu;

		if( isset($this->page["links"]) )
		{
			$this->pageData["links"] = $this->page["links"];
		}

		switch($this->page["name"])
		{
			case "index": $this->loadContentIndex(); break;
			case "system": $this->loadContentSystem(); break;
			case "license": $this->loadContentLicense(); break;
			default: $this->loadContent404(); break;
		}
	}

	protected function loadContentIndex()
	{
		$name = $this->app->system("name", "Elastic-CMF");

		$this->pageData["content"] = "<h3>{$name}</h3>

<p>Elastic CMF (Content Management Framework) 
is a system that facilitates the use of reusable components or customized 
software for managing Web content. It shares aspects of a Web application 
framework and a content management system (CMS).</p>

<p>This is the default page. You did not specify mount points.</p>";
	}

	protected function loadContentSystem()
	{
		$app = $this->app;
		$db = Prop::prop("db")->getArray("default");

		$body  = "<h3>Base info</h3>";
		$body .= "<strong>Domain:</strong> " . $app->host->getName() . "<br>";
		$body .= "<strong>Web-site:</strong> " . $app->system("siteName", $app->host->getName()) . "<br>";
		$body .= "<strong>PHP Version:</strong> " . PHP_VERSION . "</p>";

		$driver = $db["driver"] ?? "mysql";

		try {
			$ping = $app->db->ping();
			$originalDriver = $app->db->getDbalConnection()->getDriver()->getName();
			if(!empty($originalDriver))
			{
				$driver = $originalDriver;
			}
		}
		catch(DBALException $e) {
			if( !isset($ping) )
			{
				$ping = false;
			}
		}

		$body .= "<h3>Database</h3>";
		$body .= "<p><strong>Driver:</strong> " . $driver . "<br>";
		$body .= "<strong>Charset:</strong> " . ($db["charset"] ?? "-") . "<br>";
		if( ! empty($db["collation"]) ) $body .= "<strong>Collation:</strong> " . $db["collation"] . "<br>";
		if( ! empty($db["prefix"]) ) $body .= "<strong>Prefix:</strong> " . $db["prefix"] . "<br>";
		$body .= "<strong>Ping:</strong> " . ($ping ? "true" : "false");
		$body .= "</p>";

		$body .= "<h3>Modules</h3>";

		/** @var Modules_SchemeDesigner[] $modules */
		$modules = $this
			->app
			->db
			->table(Modules_SchemeDesigner::class)
			->orderBy("name")
			->get();

		foreach( $modules as $module )
		{
			$manifest = $module->getManifest();

			$body .= '<p><strong>' . $module->getName() . ':</strong> ' . $manifest->getTitle();
			$body .= ' v&nbsp;' . $manifest->getVersion();
			if( ! $module->isInstall() )
			{
				$body .= ' <span class="warn">[not install]</span>';
			}

			$licenses = $manifest->getLicenses();
			if(count($licenses))
			{
				$body .= '<br>Licenses: ' . implode(", ", $licenses);
			}

			$authors = $manifest->getAuthors();
			if(count($authors))
			{
				$all = [];
				foreach($authors as $author)
				{
					$name = $author["name"];
					if( !empty($author["email"]) )
					{
						$name .= '&nbsp;&lt;' . $author["email"] . '&gt;';
					}
					$all[] = $name;
				}
				$body .= '<br>Authors: ' . implode(", ", $all);
			}

			$description = $manifest->getDescription();
			if( ! empty($description) )
			{
				$body .= '<br>' . $description;
			}

			$body .= '</p>';
		}

		$this->pageData["content"] = $body;
	}

	protected function loadContentLicense()
	{
		$this->pageData["content"] = '<h3>MIT License</h3>

<p>Copyright (c) 2018 RozaVerta</p>

<p>Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:</p>

<p>The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.</p>

<p>THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.</p>';
	}

	protected function loadContent404()
	{
		$this->pageData["content"] = '<h3>404</h3><p>' . $this->app->url->getUrl() . '</p><p>The page are you looking for cannot be found.</p>';
	}
}