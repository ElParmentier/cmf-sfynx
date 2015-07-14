#MULTISTAGE CONFIGURATION
set :stages, %w(develop preprod prod)
set :default_stage, "azure-dev"
set :stage_dir, 'config/deploy'
require 'capistrano/ext/multistage'

# GLOBAL CONFIGURATION
set :application, "sfynx23"
set :app_path,    "app"
set :web_path,    "web"
set :maintenance_basename,  "Sfynx est en cours de maintenance"

# REPOSITORY
set :scm,         :git

# VENDORS AND SHARING PATH
set :use_composer,      true
set :update_vendors,    false
set :install_vendors,   true
set :copy_vendors, true
set :deploy_via,  :remote_cache
set :composer_options,  "--verbose --prefer-dist"
set :shared_files,      ["app/config/parameters.yml", "web/robots.txt"]
set :shared_children,   [app_path + "/logs", app_path + "/cachesfynx", web_path + "/uploads"]

# ORM
set :model_manager, "doctrine"

# CAPISTRANO
set :use_sudo,      false
# Be more verbose by uncommenting the following line
# logger.level = Logger::MAX_LEVEL


# PERMISSIONS
set :writable_dirs,     ["app/cache", "app/logs", "web/uploads"]
set :permission_method, :acl

## Custom Tasks

##Setup
namespace :deploy do
  task :setup do
    capifony_puts_ok
  end
end
 
##Reset all project's Datas
namespace :sfynxnamespace do
  task :reset_data_dev, :except => { :no_release => true } do
    capifony_pretty_print "--> Reset all project data"
    run "${try_sudo} sh -c 'cd #{latest_release} && echo y | ./resetProjectDataDev.sh'"
    capifony_puts_ok
  end
end

namespace :sfynxnamespace do
  task :reset_data_preprod, :except => { :no_release => true } do
    capifony_pretty_print "--> Reset all project data"
    run "${try_sudo} sh -c 'cd #{latest_release} && echo y | ./resetProjectDataPreProd.sh'"
    capifony_puts_ok
  end
end

namespace :sfynxnamespace do
  task :reset_data_prod, :except => { :no_release => true } do
    capifony_pretty_print "--> Reset all project data"
    run "${try_sudo} sh -c 'cd #{latest_release} && echo y | ./resetProjectDataProd.sh'"
    capifony_puts_ok
  end
end
