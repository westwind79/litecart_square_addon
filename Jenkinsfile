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
      }
    }
  }
}
