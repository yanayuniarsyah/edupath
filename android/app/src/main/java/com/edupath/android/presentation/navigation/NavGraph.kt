package com.edupath.android.presentation.navigation

import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.platform.LocalContext
import androidx.navigation.compose.NavHost
import androidx.navigation.compose.composable
import androidx.navigation.compose.rememberNavController
import com.edupath.android.core.datastore.SessionStoreImpl
import com.edupath.android.presentation.auth.LoginScreen
import com.edupath.android.presentation.auth.RegisterScreen
import com.edupath.android.presentation.home.HomeScreen
import kotlinx.coroutines.delay
import kotlinx.coroutines.flow.first

@Composable
fun EduPathNavGraph() {
    val navController = rememberNavController()

    NavHost(navController = navController, startDestination = "splash") {
        composable("splash") {
            SplashScreen(onSplashFinished = { hasToken ->
                if (hasToken) {
                    navController.navigate("home") {
                        popUpTo("splash") { inclusive = true }
                    }
                } else {
                    navController.navigate("login") {
                        popUpTo("splash") { inclusive = true }
                    }
                }
            })
        }
        
        composable("login") {
            LoginScreen(
                onLoginSuccess = {
                    navController.navigate("home") {
                        popUpTo("login") { inclusive = true }
                    }
                },
                onNavigateRegister = {
                    navController.navigate("register")
                }
            )
        }
        
        composable("register") {
            RegisterScreen(
                onRegisterSuccess = {
                    navController.navigate("home") {
                        popUpTo("register") { inclusive = true }
                        popUpTo("login") { inclusive = true }
                    }
                },
                onNavigateLogin = {
                    navController.popBackStack()
                }
            )
        }
        
        composable("home") {
            HomeScreen(
                onNavigateLogout = {
                    navController.navigate("login") {
                        popUpTo("home") { inclusive = true }
                    }
                }
            )
        }
    }
}

@Composable
fun SplashScreen(onSplashFinished: (Boolean) -> Unit) {
    val context = LocalContext.current
    
    LaunchedEffect(key1 = true) {
        val store = SessionStoreImpl(context)
        val token = store.accessToken.first()
        delay(1500)
        onSplashFinished(token != null)
    }
    
    Box(
        modifier = Modifier.fillMaxSize(),
        contentAlignment = Alignment.Center
    ) {
        Text("EduPath Splash")
    }
}
