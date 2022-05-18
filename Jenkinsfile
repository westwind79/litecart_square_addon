pipeline {
  agent none
  stages {
    stage("deploy") {
      stages {

        stage("staging") {
          when { branch 'staging' }
          agent { label 'staging' }
          steps {
            sh 'cp -R public_html/includes /var/www/shop-test.nokware.net/includes'
			sh 'cp -R public_html/images /var/www/shop-test.nokware.net/images'
          }
        }
		
		stage ("production"){
		  when { branch 'master' }
		  agent { label 'production' }
		  stages {
		    stage("backtothelan.com") {
			  sh 'cp -R public_html/includes /var/www/backtothelan.com/includes'
			  sh 'cp -R public_html/images /var/www/backtothelan.com/images'
			}
			
			stage("shop.nokware.net") {
			  sh 'cp -R public_html/includes /var/www/shop.nokware.net/includes'
			  sh 'cp -R public_html/images /var/www/shop.nokware.net/images'
			}
			
			stage("sugarhousecoins.com") {
			  sh 'cp -R public_html/includes /var/www/sugarhousecoins.com/includes'
			  sh 'cp -R public_html/images /var/www/sugarhousecoins.com/images'
			}
		  }
		}
      }
    }
  }
}
