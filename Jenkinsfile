pipeline {
  agent none
  stages {
    stage("build") {
      agent { dockerfile true }

      steps {
        stash excludes: '.git/,.gitignore,Dockerfile,Jenkinsfile', name: 'stripe'
      }
    }
  
    stage("deploy") {
      stages {

        stage("staging") {
          when { branch 'staging' }
          agent { label 'staging' }
          steps {
            unstash 'stripe'
            sh 'cp -R public_html/includes /var/www/shop-test.nokware.net/includes'
			sh 'cp -R public_html/images /var/www/shop-test.nokware.net/images'
          }
        }
      }
    }
  }
}
