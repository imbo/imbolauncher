require 'fileutils'
require 'digest/md5'
require 'json'

basedir  = "."
build    = "#{basedir}/build"
source   = "#{basedir}/src"
tests    = "#{basedir}/tests"

desc "Default task"
task :default => [:prepare, :lint, :installdep, :phpunit]

desc "Clean up and create artifact directories"
task :prepare do
  FileUtils.rm_rf build
  FileUtils.mkdir build

  ["coverage"].each do |d|
    FileUtils.mkdir "#{build}/#{d}"
  end
end

desc "Check syntax on all php files in the project"
task :lint do
  lintCache = "#{basedir}/.lintcache"

  begin
    sums = JSON.parse(IO.read(lintCache))
  rescue Exception => foo
    sums = {}
  end

  `git ls-files "*.php"`.split("\n").each do |f|
    f = File.absolute_path(f)
    md5 = Digest::MD5.hexdigest(File.read(f))

    next if sums[f] == md5

    sums[f] = md5

    begin
      sh %{php -l #{f}}
    rescue Exception
      exit 1
    end
  end

  IO.write(lintCache, JSON.dump(sums))
end

desc "Run PHPUnit tests"
task :phpunit do
  begin
    sh %{vendor/bin/phpunit --verbose --coverage-html build/coverage --coverage-clover build/logs/clover.xml --log-junit build/logs/junit.xml}
  rescue Exception
    exit 1
  end
end
